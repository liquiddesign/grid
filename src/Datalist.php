<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Control;
use Nette\Utils\Paginator;
use StORM\Collection;
use StORM\ICollection;

/**
 * @method onLoad(\StORM\ICollection $source)
 * @method onSaveState(\Grid\Datalist $param, array $params)
 * @method onLoadState(\Grid\Datalist $param, array $params)
 */
class Datalist extends Control
{
	/**
	 * @var callable[]&callable(\Grid\Form ): void[] ; Occurs before data is load
	 */
	public $onLoad;
	
	/**
	 * @var callable[]&callable(\Grid\Form ): void[] ; Occurs before state is loaded
	 */
	public $onLoadState;
	
	/**
	 * @var callable[]&callable(\Grid\Form ): void[] ; Occurs after state is save
	 */
	public $onSaveState;
	
	/**
	 * @persistent
	 */
	public ?string $order = null;
	
	/**
	 * @persistent
	 */
	public int $page = 1;
	
	/**
	 * @persistent
	 */
	public ?int $onpage = null;
	
	protected ?int $defaultOnPage = null;
	
	protected ?string $defaultOrder = null;
	
	protected string $defaultDirection = 'ASC';
	
	/**
	 * @var string[]
	 */
	protected array $secondaryOrder = [];
	
	/**
	 * @var string[]
	 */
	protected array $allowedOrderColumn = [];
	
	/**
	 * @var string[]
	 */
	protected array $orderExpressions = [];
	
	/**
	 * @var string[]|callable[]
	 */
	protected array $filterExpressions = [];
	
	/**
	 * @var mixed[]|null[]
	 */
	protected array $filterDefaultValue = [];
	
	/**
	 * @var string[]
	 */
	protected array $allowedRepositoryFilters = [];
	
	/**
	 * @var callable[]
	 */
	protected array $filters = [];
	
	protected bool $autoCanonicalize = false;
	
	protected Paginator $paginator;
	
	protected ICollection $source;
	
	protected ?ICollection $filteredSource = null;
	
	/**
	 * @var \StORM\Entity[]|object[]|null
	 */
	protected ?array $itemsOnPage = null;
	
	/**
	 * @var callable|null
	 */
	protected $nestingCallback = null;
	
	public function __construct(ICollection $source)
	{
		$this->paginator = new Paginator();
		$this->source = $source;
		
		if (!($source instanceof Collection)) {
			return;
		}

		foreach ($source->getRepository()->getStructure()->getColumns(true) as $column) {
			$this->allowedOrderColumn[$column->getPropertyName()] = $column->getName();
		}
	}
	
	public function setDefaultOnPage(?int $onPage): void
	{
		$this->defaultOnPage = $onPage;
	}
	
	public function getDefaultOnPage(): ?int
	{
		return $this->defaultOnPage;
	}
	
	public function setDefaultOrder(?string $name, string $direction = 'ASC'): void
	{
		$this->defaultOrder = $name;
		$this->defaultDirection = $direction;
	}
	
	public function setSecondaryOrder(array $orderBy): void
	{
		$this->secondaryOrder = $orderBy;
	}
	
	public function getDirection(bool $reverse = false): string
	{
		if ($this->order === null) {
			$orderDirection = $this->defaultDirection;
		} else {
			[$name, $orderDirection] = \explode('-', $this->order);
			unset($name);
		}
		
		if ($reverse) {
			return $orderDirection === 'ASC' ? 'DESC' : 'ASC';
		}
		
		return $orderDirection;
	}
	
	public function getOrder(): ?string
	{
		if ($this->order === null) {
			return $this->defaultOrder;
		}
		
		[$name, $direction] = \explode('-', $this->order);
		unset($direction);
		
		return $name;
	}
	
	public function getOrderParameter(): string
	{
		return $this->getOrder() . '-' . $this->getDirection();
	}
	
	public function setAllowedOrderColumns(array $columns): void
	{
		$this->allowedOrderColumn = $columns;
	}
	
	public function addOrderExpression(string $name, callable $callback): void
	{
		$this->orderExpressions[$name] = $callback;
	}
	
	/**
	 * @param string[] $listToRemove
	 */
	public function removeOrderExpressions(array $listToRemove): void
	{
		foreach ($listToRemove as $name) {
			unset($this->orderExpressions[$name]);
		}
	}
	
	public function setOrder(?string $name, string $direction = 'ASC'): void
	{
		$this->order = $name . '-' . $direction;
	}
	
	public function addFilterExpression($name, callable $callback, $defaultValue = null): void
	{
		$this->filterExpressions[$name] = $callback;
		$this->filterDefaultValue[$name] = $defaultValue;
	}
	
	public function removeFilterExpressions(array $listToRemove): void
	{
		foreach ($listToRemove as $name) {
			unset($this->filterExpressions[$name]);
			unset($this->filterDefaultValue[$name]);
		}
	}
	
	public function setAllowedRepositoryFilters(array $list): void
	{
		$this->allowedRepositoryFilters = $list;
	}
	
	public function setFilters(array $filters): void
	{
		$this->filters = $filters;
	}
	
	/**
	 * @return callable[]
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}
	
	public function setPage(int $page): void
	{
		$this->page = $page;
	}
	
	public function getPage(): int
	{
		return $this->page;
	}
	
	public function setOnPage(?int $onPage): void
	{
		$this->onpage = $onPage;
	}
	
	public function getOnPage(): ?int
	{
		return $this->onpage ?: $this->defaultOnPage;
	}
	
	public function loadState(array $params): void
	{
		$this->onLoadState($this, $params);
		
		parent::loadState($params);
		
		foreach ($params as $name => $value) {
			if (isset($this->filterExpressions[$name])) {
				$this->filters[$name] = $value;
			}
		}
	}
	
	public function saveState(array &$params): void
	{
		parent::saveState($params);
		
		$this->onSaveState($this, $params);
		
		if ($this->autoCanonicalize) {
			if (isset($params['onpage']) && $this->defaultOnPage !== null && $this->defaultOnPage === (int)$params['onpage']) {
				$params['onpage'] = null;
			}
			
			if (isset($params['order']) && $this->defaultOrder !== null && ($this->defaultOrder . '-' . $this->defaultDirection) === $params['order']) {
				$params['order'] = null;
			}
		}
		
		if (!$this->autoCanonicalize) {
			if ($params['page'] === null) {
				// default page
				$params['page'] = 1;
			}
		}
		
		if (!$this->filters) {
			return;
		}

		foreach ($this->filters as $filter => $value) {
			$params[$filter] = $value;
		}
	}
	
	public function setAutoCanonicalize(bool $enabled): void
	{
		$this->autoCanonicalize = $enabled;
	}
	
	public function getSource(bool $newInstance = true): ICollection
	{
		return $newInstance ? clone $this->source : $this->source;
	}
	
	public function getFilteredSource(bool $newInstance = true): ICollection
	{
		if ($this->filteredSource && !$newInstance) {
			return $this->filteredSource;
		}
		
		$filteredSource = $this->getSource();
		
		// FILTER
		foreach ($this->filters as $name => $value) {
			if ($filteredSource instanceof Collection && !isset($this->filterExpressions[$name]) && \in_array($name, $this->allowedRepositoryFilters)) {
				$filteredSource->filter([$name => $value]);
			}
			
			if (!isset($this->filterExpressions[$name]) || $this->filterDefaultValue[$name] === $value) {
				continue;
			}
			
			\call_user_func_array($this->filterExpressions[$name], [$filteredSource, $value]);
		}
		
		// ORDER BY
		if ($this->getOrder() !== null) {
			$filteredSource->setOrderBy([]);
			
			if (isset($this->orderExpressions[$this->getOrder()])) {
				\call_user_func_array($this->orderExpressions[$this->getOrder()], [$filteredSource, $this->getDirection()]);
			}

			if (isset($this->allowedOrderColumn[$this->getOrder()])) {
				$filteredSource->orderBy([$this->allowedOrderColumn[$this->getOrder()] => $this->getDirection()]);
			}
			
			$filteredSource->orderBy($this->secondaryOrder);
		}
		
		if ($newInstance) {
			return $filteredSource;
		}
		
		return $this->filteredSource = $filteredSource;
	}
	
	public function getPaginator(bool $current = false): \Nette\Utils\Paginator
	{
		if ($current) {
			return $this->paginator;
		}
		
		$this->paginator->setPage($this->getPage());
		
		if ($this->getOnPage()) {
			$this->paginator->setItemsPerPage($this->getOnPage());
		}
		
		$this->paginator->setItemCount($this->getFilteredSource()->count());
		
		return $this->paginator;
	}

	/**
	 * @return \StORM\Entity[]|object[]
	 */
	public function getItemsOnPage(): array
	{
		if ($this->itemsOnPage !== null) {
			return $this->itemsOnPage;
		}
		
		$source = $this->getFilteredSource();
		
		if ($this->getPaginator()->getItemsPerPage() !== 1) {
			$source->setPage($this->getPaginator()->getPage(), $this->getPaginator()->getItemsPerPage());
		}
		
		$this->onLoad($source);
		
		$this->itemsOnPage = $this->nestingCallback ? $this->getNestedSource($source, null) : $source->toArray();
		
		return $this->itemsOnPage;
	}
	
	public function setNestingCallback(callable $callback): void
	{
		$this->nestingCallback = $callback;
	}

	public static function loadSession(\Nette\Http\SessionSection $session): void
	{
		function ($datalist, $params) use ($session): void {
			if (!isset($params['page']) && isset($session->page)) {
				$datalist->page = $session->page;
			}
			
			if (isset($params['onpage']) || !isset($session->onpage)) {
				return;
			}

			$datalist->onpage = $session->onpage;
		};
	}
	
	public static function saveSession(\Nette\Http\SessionSection $session): void
	{
		function ($datalist, $params) use ($session): void {
			if (isset($params['page'])) {
				/* @phpstan-ignore-next-line */
				$session->page = $params['page'];
			} else {
				unset($session->page);
			}
			
			if (isset($params['onpage'])) {
				/* @phpstan-ignore-next-line */
				$session->onpage = $params['onpage'];
			} else {
				unset($session->onpage);
			}
		};
	}
	
	/**
	 * @param \StORM\ICollection $source
	 * @param \StORM\Entity|object|null $parent
	 * @return \StORM\Entity[]|object[]
	 */
	protected function getNestedSource(ICollection $source, ?object $parent): array
	{
		$items = [];
		\call_user_func_array($this->nestingCallback, [$source, $parent]);
		
		/* @phpstan-ignore-next-line */
		foreach ($source as $item) {
			$items[] = $item;
			$items = \array_merge($items, $this->getNestedSource($this->getFilteredSource(true), $item));
		}
		
		return $items;
	}
}
