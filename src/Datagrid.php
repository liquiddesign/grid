<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\Checkbox;
use Nette\InvalidStateException;
use Nette\Utils\Html;
use StORM\Collection;
use StORM\Entity;
use StORM\ICollection;

/**
 * Class Datagrid
 * @property \Nette\Bridges\ApplicationLatte\Template|\StdClass $template
 * @method onRenderRow(\Nette\Utils\Html $tr, $entity, int|string $id)
 * @method onRender(\Nette\Utils\Html $body, array|\Grid\Column[] $columns)
 * @method onUpdateRow(mixed $id, array|mixed[] $values)
 * @method onDeleteRow(object $object)
 * @method \Nette\Forms\Controls\TextInput addFilterText(callable $filterExpression, ?string $defaultValue, string $name, $label = null, int $cols = null, int $maxLength = null)
 * @method \Nette\Forms\Controls\TextInput addFilterPassword(callable $filterExpression, ?string $defaultValue, string $name, $label = null, int $cols = null, int $maxLength = null)
 * @method \Nette\Forms\Controls\TextArea addFilterTextArea(callable $filterExpression, ?string $defaultValue, string $name, $label = null, int $cols = null, int $maxLength = null)
 * @method \Nette\Forms\Controls\TextInput addFilterEmail(callable $filterExpression, ?string $defaultValue, string $name, $label = null)
 * @method \Nette\Forms\Controls\TextInput addFilterInteger(callable $filterExpression, ?string $defaultValue, string $name, $label = null)
 * @method \Nette\Forms\Controls\UploadControl addFilterUpload(callable $filterExpression, ?string $defaultValue, string $name, $label = null)
 * @method \Nette\Forms\Controls\UploadControl addFilterMultiUpload(callable $filterExpression, ?string $defaultValue, string $name, $label = null)
 * @method \Nette\Forms\Controls\Checkbox addFilterCheckbox(callable $filterExpression, ?string $defaultValue, string $name, $caption = null)
 * @method \Nette\Forms\Controls\RadioList addFilterRadioList(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null)
 * @method \Nette\Forms\Controls\CheckboxList addFilterCheckboxList(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null)
 * @method \Nette\Forms\Controls\SelectBox addFilterSelect(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null, int $size = null)
 * @method \Nette\Forms\Controls\SelectBox addFilterDataSelect(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null, int $size = null)
 * @method \Nette\Forms\Controls\SelectBox addFilterSelect2(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null, int $size = null)
 * @method \Nette\Forms\Controls\MultiSelectBox addFilterMultiSelect(callable $filterExpression, ?string $defaultValue, string $name, $label = null, array $items = null, int $size = null)
 * @method \Nette\Forms\Controls\MultiSelectBox addFilterDataMultiSelect(callable $filterExpression,?string $defaultValue, string $name, $label = null, array $items = null, ?array $configuration = [])
 * @method \Nette\Forms\Controls\UploadControl addFilterImage(callable $filterExpression, ?string $defaultValue, string $name, string $src = null, string $alt = null)
 * @method \Nette\Forms\Controls\TextInput addFilterDate(callable $filterExpression, ?string $defaultValue, string $name, ?string $label = null, ?array $configuration = [])
 * @method \Nette\Forms\Controls\TextInput addFilterDatetime(callable $filterExpression, ?string $defaultValue, string $name, ?string $label = null, ?array $configuration = [])
 */
class Datagrid extends Datalist
{
	/**
	 * @var array<callable(\Nette\Utils\Html, array): void> Called after render
	 */
	public array $onRender = [];
	
	/**
	 * @var array<callable(\Nette\Utils\Html, array): void> Called after render Row
	 */
	public array $onRenderRow = [];
	
	/**
	 * @var array<callable(mixed, array): void> Called before update Row
	 */
	public array $onUpdateRow = [];
	
	/**
	 * @var array<callable(object): void> Called before delete Row
	 */
	public array $onDeleteRow = [];
	
	/**
	 * @var callable|null
	 */
	protected $encodeIdCallback = null;
	
	/**
	 * @var callable|null
	 */
	protected $decodeIdCallback = null;
	
	/**
	 * @var callable|null
	 */
	protected $idCallback = null;
	
	protected ?string $sourceIdName = null;
	
	/**
	 * @var \Grid\Column[]
	 */
	protected array $columns = [];
	
	/**
	 * @var mixed[]
	 */
	protected array $inputs = [];
	
	/**
	 * @var mixed[]
	 */
	protected array $inputsValues = [];
	
	/**
	 * @var callable[]
	 */
	protected array $actions = [];
	
	public function __construct(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		if ($encodeId) {
			$this->encodeIdCallback = static function ($id) {
				return \bin2hex((string) $id);
			};
			
			$this->decodeIdCallback = static function ($id) {
				return (string) \hex2bin((string) $id);
			};
		}
		
		if ($source instanceof Collection) {
			$this->idCallback = static function (Entity $object) {
				return $object->getPK();
			};
			$this->sourceIdName = $source->getRepository()->getStructure()->getPK()->getName();
		}
		
		parent::__construct($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir);
		
		// replace item count callback
		$this->itemCountCallback = function (ICollection $filteredSource) {
			return !$filteredSource->isLoaded() && $this->getSourceIdName() ?
				$filteredSource->setGroupBy([])->enum($filteredSource->getPrefix(true) . $this->getSourceIdName(), true) : $filteredSource->setGroupBy([])->count();
		};
	}
	
	public function setEncodeCallbacks(?callable $encodeCallback, ?callable $decodeCallback): void
	{
		$this->encodeIdCallback = $encodeCallback;
		$this->decodeIdCallback = $decodeCallback;
	}
	
	public function setSourceId(callable $idCallback, string $sourceIdName): void
	{
		$this->idCallback = $idCallback;
		$this->sourceIdName = $sourceIdName;
	}
	
	/**
	 * @param callable $callable
	 * @deprecated use setSourceId instead
	 */
	public function setGetIdCallback(callable $callable): void
	{
		$this->idCallback = $callable;
	}
	
	public function getSourceIdName(): string
	{
		if (!$this->sourceIdName) {
			throw new InvalidStateException('Cannot get source ID name');
		}
		
		return $this->sourceIdName;
	}
	
	/**
	 * @return string[]
	 */
	public function getSelectedIds(): array
	{
		$array = \array_values($this->getForm()->getHttpData($this->getForm()::DATA_TEXT | $this->getForm()::DATA_KEYS, '__selector[]'));
		
		if ($this->decodeIdCallback) {
			$array = \array_map($this->decodeIdCallback, $array);
		}
		
		return $array;
	}
	
	public function deletedSelected(?string $idName = null): int
	{
		$source = $this->getSource();
		$idName = $idName ?: $source->getPrefix(true) . $this->getSourceIdName();
		
		return $source->where($idName, $this->getSelectedIds())->delete();
	}
	
	/**
	 * @param string|\Nette\Utils\Html $th
	 * @param callable $dataCallback
	 * @param string|\Nette\Utils\Html $td
	 * @param string|null $orderExpression
	 * @param string[] $wrapperAttributes
	 */
	public function addColumn($th, callable $dataCallback, $td = '%s', ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		if ($orderExpression && !isset($this->allowedOrderColumn[$orderExpression])) {
			$this->allowedOrderColumn[$orderExpression] = $orderExpression;
		}
		
		$id = \count($this->columns);
		
		$column = new Column($this, $th, $td, $dataCallback, $orderExpression, $wrapperAttributes);
		$column->setId($id);
		
		return $this->columns[$id] = $column;
	}
	
	public function addColumnText($th, $expressions, $td, ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		$expressions = !\is_array($expressions) ? [$expressions] : $expressions;
		$filters = $this->parseFilters($expressions);
		
		$grid = $this;
		
		return $this->addColumn($th, static function ($item) use ($expressions, $grid, $filters) {
			$vars = [];
			
			foreach ($expressions as $key => $expression) {
				$previous = $item;
				
				foreach (\explode('.', $expression) as $property) {
					if (!\is_object($previous)) {
						break;
					}
					
					$previous = \method_exists($previous, $property) ? \call_user_func([$previous, $property]) : $previous->$property;
				}
				
				foreach ($filters[$key] as $f => $args) {
					foreach ($args as $k => $v) {
						if (\is_array($v)) {
							$args[$k] = $item;

							foreach ($v as $p) {
								$args[$k] = $args[$k]->$p;
							}
						}
					}
					
					$previous = $grid->template->getLatte()->invokeFilter((string) $f, \array_merge([$previous], $args));
				}
				
				$vars[] = $previous;
			}
			
			return $vars;
		}, $td, $orderExpression, $wrapperAttributes);
	}
	
	public function handleProcess(string $name, string $id): void
	{
		$source = $this->getSource();
		
		$object = $source->where($source->getPrefix(true) . $this->getSourceIdName(), $id)->first();
		
		if (!isset($this->actions[$name]) || !$object) {
			return;
		}
		
		\call_user_func($this->actions[$name], $object, $this);
	}
	
	public function addColumnAction($th, string $td, callable $actionCallback, array $properties = [], ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		$id = \count($this->columns) + 1;
		$parent = $this;
		$this->actions[$id] = $actionCallback;
		
		return $this->addColumn($th, static function ($item) use ($properties, $parent, $id) {
			$idName = $parent->getSourceIdName();
			$vars = [$parent->link('process!', [$id, $item->$idName])];
			$properties += !\is_array($properties) ? [$properties] : $properties;
			
			foreach ($properties as $property) {
				$vars[] = $item->$property;
			}
			
			return $vars;
		}, $td, $orderExpression, $wrapperAttributes);
	}
	
	/**
	 * @param string[]|null $inputNames
	 * @return mixed[][]
	 */
	public function getInputData(?array $inputNames = null): array
	{
		$values = [];
		$flags = $this->getForm()::DATA_TEXT | $this->getForm()::DATA_KEYS;
		$ids = \array_keys($this->getItemsOnPage());
		
		foreach ($ids as $id) {
			$encodedId = $this->encodeIdCallback ? \call_user_func($this->encodeIdCallback, $id) : $id;
			$values[$id] = [];
			$inputs = $inputNames === null ? $this->inputs : \array_intersect_key($this->inputs, \array_flip($inputNames));
			
			foreach ($inputs as $name => $settings) {
				$httpData = $this->getForm()->getHttpData($flags, $name . '[]');
				$mutation = null;
				[$defaultValue, $isCheckbox] = $settings;
				
				if ($this->source instanceof Collection) {
					$column = $this->source->getRepository()->getStructure()->getColumn($name);

					if ($column && $column->hasMutations()) {
						$mutation = $this->source->getConnection()->getMutation();
					}
				}
				
				if ($isCheckbox) {
					$values[$id][$name] = $mutation ? [$mutation => isset($httpData[$encodedId])] : isset($httpData[$encodedId]);
				} elseif ($httpData[$encodedId] !== $defaultValue) {
					$values[$id][$name] = $mutation ? [$mutation => $httpData[$encodedId]] : $httpData[$encodedId];
				}
			}
		}
		
		return $values;
	}
	
	public function addColumnInput($th, string $name, callable $callback, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		$this->getForm()->addComponent(new Multiplier($callback), $name);
		
		$this->registerInput($name, $defaultValue, \call_user_func($callback, '') instanceof Checkbox);
		
		if (!$this->idCallback) {
			throw new \DomainException('ID callback is not set, call ->setGetIdCallback()');
		}
		
		return $this->addColumn($th, function ($object, $datagrid) use ($name, $setValueExpression) {
			$id = $this->encodeIdCallback ? \call_user_func($this->encodeIdCallback, \call_user_func($this->idCallback, $object)) : \call_user_func($this->idCallback, $object);
			
			$input = $datagrid['form'][$name][$id];
			
			if (\is_string($setValueExpression)) {
				$property = $setValueExpression ?: $name;
				$input->setValue($object->$property);
				$this->inputsValues[$id][$name] = $object->$property;
			}
			
			if (\is_callable($setValueExpression)) {
				\call_user_func_array($setValueExpression, [$input, $object]);
			}
			
			return $input->getControl();
		}, '%s', $orderExpression, $wrapperAttributes);
	}
	
	public function addColumnSelector(array $wrapperAttributes = []): Column
	{
		$selectorAll = $this->getForm()->addCheckbox('__selector_all')->setHtmlAttribute('onclick', "gridSelectAll(this, this.closest('table'));");
		
		$columnInput = $this->addColumnInput($selectorAll->getControl(), '__selector', function ($id) {
			return (new Checkbox())->setHtmlAttribute('value', $id)->setHtmlAttribute('class', 'rowSelector');
		}, null, null, null, $wrapperAttributes);
		
		unset($this->inputs['__selector_all'], $this->inputs['__selector']);
		
		return $columnInput;
	}
	
	public function getForm(): Form
	{
		/* @phpstan-ignore-next-line */
		return $this['form'];
	}
	
	public function getRows(): Html
	{
		$body = Html::el('tbody');
		
		foreach ($this->getItemsOnPage() as $id => $entity) {
			$tr = Html::el('tr');
			
			foreach ($this->columns as $column) {
				$tr->addHtml($column->renderCell($entity));
			}
			
			$this->onRenderRow($tr, $entity, $id);
			
			$body->addHtml($tr);
		}
		
		$this->onRender($body, $this->columns);
		
		return $body;
	}
	
	public function render(): void
	{
		$this->template->columns = $this->columns;
		$this->template->paginator = $this->paginator;
		
		if (!$this->template->getFile()) {
			$this->template->setFile(__DIR__ . '/datagrid.latte');
		}
		
		$this->template->render();
	}
	
	protected function createComponentForm(): ?IComponent
	{
		return new Form();
	}
	
	protected function registerInput(string $name, $defaultValue, bool $isCheckboxType = false): void
	{
		$this->inputs[$name] = [$defaultValue, $isCheckboxType];
	}
	
	/**
	 * @param string[] $expressions
	 * @return mixed[]
	 */
	private function parseFilters(array &$expressions): array
	{
		$filters = [];
		
		foreach ($expressions as $key => $expression) {
			$matches = [];
			$params = "(?:\:(?:('[^']*'))?([\.0-9]+)?)?";
			$filter = "(?:\|([a-zA-Z_\.0-9]+)$params$params)?";
			\preg_match("/([a-zA-Z_.0-9]+)$filter$filter/", $expression, $matches);
			$expressions[$key] = $matches[1];
			$i = 0;
			$filters[$key] = [];
			$currentFilter = null;

			foreach (\array_slice($matches, 2) as $value) {
				if ($i % 5 === 0) {
					$currentFilter = $value;
					$filters[$key][$currentFilter] = [];
				} elseif ($value !== '') {
					$filters[$key][$currentFilter][] = $i % 5 % 2 === 0 ? (\is_numeric($value) ? \floatval($value) : \explode('.', $value)) : \trim($value, "'");
				}
				
				$i++;
			}
		}
		
		return $filters;
	}
}
