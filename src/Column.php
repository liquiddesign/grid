<?php

namespace Grid;

use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\SmartObject;
use Nette\Utils\Html;
use StORM\Collection;

/**
 * @method onRender(\Nette\Utils\Html $th)
 * @method onRenderCell(\Nette\Utils\Html $td, object $object)
 */
class Column
{
	use SmartObject;
	
	/**
	 * @var callable[]
	 */
	public $onRender;
	
	/**
	 * @var callable[]
	 */
	public $onRenderCell;
	
	private int $id;
	
	/**
	 * @var string|\Nette\Utils\Html
	 */
	private $th;
	
	/**
	 * @var string|\Nette\Utils\Html
	 */
	private $td;
	
	/**
	 * @var callable
	 */
	private $dataCallback;
	
	private \Nette\Utils\Html $wrapper;
	
	private ?\Nette\Forms\Container $container = null;
	
	private ?string $orderExpression;
	
	private Datagrid $datagrid;
	
	/**
	 * @var string[]
	 */
	private array $wrapperAttributes;
	
	/**
	 * Column constructor.
	 * @param Datagrid $datagrid
	 * @param \Nette\Utils\Html|string $th
	 * @param \Nette\Utils\Html|string $td
	 * @param callable $dataCallback
	 * @param string|null $orderName
	 * @param string[] $wrapperAttributes
	 */
	public function __construct(Datagrid $datagrid, $th, $td, callable $dataCallback, ?string $orderName = null, array $wrapperAttributes = [])
	{
		$this->datagrid = $datagrid;
		$this->th = $th;
		$this->td = $td;
		$this->dataCallback = $dataCallback;
		$this->orderExpression = $orderName;
		$this->wrapperAttributes = $wrapperAttributes;
	}
	
	public function setContainer(Container $container): void
	{
		$this->container = $container;
	}
	
	public function getContainer(): ?Container
	{
		return $this->container;
	}
	
	public function setId(int $id): void
	{
		$this->id = $id;
	}
	
	public function getId(): int
	{
		return $this->id;
	}
	
	public function getTableHead(): string
	{
		return $this->th;
	}
	
	public function renderHeaderCell(): Html
	{
		$th = Html::el('th');
		foreach ($this->wrapperAttributes as $name => $value) {
			$th->setAttribute($name, $value);
		}
		
		if ($this->isOrdable()) {
			$a = Html::el('a');
			$a->setAttribute('href', $this->datagrid->link('this', ['order' => $this->getOrderExpression() . '-' . $this->datagrid->getDirection(true)]));
			$a->setHtml($this->th);
			$th->setHtml($a);
		} else {
			$th->setHtml($this->th);
		}
		
		$this->onRender($th);
		
		return $th;
	}
	
	public function renderCell(object $object): Html
	{
		$td = Html::el('td');
		$parameters = \call_user_func_array($this->dataCallback, [$object, $this->container ?: $this->datagrid, $td]);
		$args = [];
		
		if ($parameters === null) {
			return $td;
		}

		$parameters = !\is_array($parameters) ? [$parameters] : $parameters;
		
		foreach ($parameters as $p) {
			$args[] = $p instanceof BaseControl ? (string) $p->getControlPart() : $p;
		}
		
		$td->setHtml(\vsprintf($this->td, $args));
		
		$this->onRenderCell($td, $object);
		
		return $td;
	}
	
	public function getTableDataExpression(): string
	{
		return $this->td;
	}
	
	public function getDataCallback(): callable
	{
		return $this->dataCallback;
	}
	
	public function isOrdable(): bool
	{
		return $this->orderExpression !== null;
	}
	
	public function isOrderNumerical(): ?bool
	{
		$source = $this->datagrid->getSource(true);
		
		if ($source instanceof Collection) {
			$column = $source->getRepository()->getStructure()->getColumn($this->orderExpression);
			
			if ($column) {
				return $column->getPropertyType() === 'int' || $column->getPropertyType() === 'float';
			}
		}
		
		return null;
	}
	
	public function getOrderExpression(): ?string
	{
		return $this->orderExpression;
	}
	
	public function getAttribute(string $name): string
	{
		return $this->wrapperAttributes[$name] ?? '';
	}
}
