<?php

namespace Grid;

use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\SmartObject;
use Nette\Utils\Html;
use StORM\Collection;

/**
 * @method onRender(\Nette\Utils\Html $tdWrapper, object $object)
 */
class Column
{
	use SmartObject;
	
	/**
	 * @var callable[]&callable(\Grid\Form ): void[] ; Called after render
	 */
	public $onRender;
	
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
	
	private string $wrapperTag;
	
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
		$this->th = $th;
		$this->td = $td;
		$this->dataCallback = $dataCallback;
		$this->wrapperTag = 'th';
		$this->orderExpression = $orderName;
		$this->datagrid = $datagrid;
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
	
	public function getWrapper(): Html
	{
		return $this->wrapper ?? $this->wrapper = Html::el($this->wrapperTag);
	}
	
	public function getTableHead(): string
	{
		return $this->th;
	}
	
	public function renderCell(object $object): string
	{
		$tdWrapper = Html::el('td');
		$parameters = \call_user_func_array($this->dataCallback, [$object, $this->container ?: $this->datagrid, $tdWrapper]);
		$args = [];
		
		if ($parameters === null) {
			return '';
		}

		$parameters = !\is_array($parameters) ? [$parameters] : $parameters;
		
		foreach ($parameters as $p) {
			$args[] = $p instanceof BaseControl ? (string) $p->getControlPart() : $p;
		}
		
		$tdWrapper->setHtml(\vsprintf($this->td, $args));
		
		$this->onRender($tdWrapper, $object);
		
		return $tdWrapper;
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
