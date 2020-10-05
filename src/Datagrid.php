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

/**
 * Class Datagrid
 * @property \Nette\Bridges\ApplicationLatte\Template|\StdClass $template
 * @method onRenderRow(\Nette\Utils\Html $tr, $entity, int|string $id)
 * @method onRender(\Nette\Utils\Html $body, array|\Grid\Column[] $columns)
 */
class Datagrid extends Datalist
{
	/**
	 * @var callable[]&callable(\Nette\Application\UI\Form ): void[] ; Called after render
	 */
	public $onRender;
	
	/**
	 * @var callable[]&callable(\Nette\Application\UI\Form ): void[] ; Called after render Row
	 */
	public $onRenderRow;
	
	/**
	 * @var \Grid\Column[]
	 */
	protected array $columns = [];
	
	/**
	 * @var mixed[]
	 */
	protected array $inputs = [];
	
	public function getSourceIdName(): string
	{
		if (!$this->source instanceof Collection) {
			throw new InvalidStateException('Cannot get source ID name');
		}
		
		return $this->source->getRepository()->getStructure()->getPK()->getName();
	}
	
	/**
	 * @return string[]
	 */
	public function getSelectedIds(): array
	{
		return \array_values($this->getForm()->getHttpData($this->getForm()::DATA_TEXT | $this->getForm()::DATA_KEYS, '__selector[]'));
	}
	
	public function deletedSelected(?string $idName = null): int
	{
		$idName = $idName ?: $this->getSourceIdName();

		return $this->getSource()->where($idName, $this->getSelectedIds())->delete();
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
		$id = \count($this->columns);
		$column = new Column($this, $th, $td, $dataCallback, $orderExpression, $wrapperAttributes);
		$column->setId($id);
		$this->columns[$id] = $column;
		
		return $column;
	}

	public function addColumnText($th, $properties, $td, ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		return $this->addColumn($th, static function ($item) use ($properties) {
			$vars = [];
			$properties = !\is_array($properties) ? [$properties] : $properties;
			
			foreach ($properties as $property) {
				$vars[] = $item->$property;
			}
			
			return $vars;
		}, $td, $orderExpression, $wrapperAttributes);
	}
	
	public function addColumnLink($th, string $linkText, string $link, string $linkClass = '', ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		return $this->addColumn(
			$th,
			static function (Entity $item, Datagrid $table) use ($link) {
				return $table->getPresenter()->link($link, $item->getPK());
			},
			'<a href="%s" class="' . $linkClass . '">' . $linkText . '</a>',
			$orderExpression,
			$wrapperAttributes + ['class' => 'minimal'],
		);
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
			$values[$id] = [];
			$inputs = $inputNames === null ? $this->inputs : \array_intersect_key($this->inputs, \array_flip($inputNames));
			
			foreach ($inputs as $name => $settings) {
				$httpData = $this->getForm()->getHttpData($flags, $name . '[]');
				[$defaultValue, $isCheckbox] = $settings;
				
				if ($isCheckbox) {
					$values[$id][$name] = isset($httpData[$id]);
				} elseif ($httpData[$id] !== $defaultValue) {
					$values[$id][$name] = $httpData[$id];
				}
			}
		}
		
		return $values;
	}

	public function addColumnInput($th, string $name, callable $callback, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		$this->getForm()->addComponent(new Multiplier($callback), $name);
		
		$this->registerInput($name, $defaultValue, \call_user_func($callback, '') instanceof Checkbox);
		
		// TODO get row ID callbacl -> never $object->getPK() consider object
		return $this->addColumn($th, function ($object, $datagrid) use ($name, $setValueExpression) {
			$input = $datagrid['form'][$name][$object->getPK()];
			
			if (\is_string($setValueExpression)) {
				$property = $setValueExpression ?: $name;
				$input->setValue($object->$property);
			}
			
			if (\is_callable($setValueExpression)) {
				\call_user_func_array($setValueExpression, [$input, $object]);
			}
			
			return $input->getControl();
		}, '%s', $orderExpression, $wrapperAttributes);
	}
	
	public function addColumnSelector(): Column
	{
		$selectorAll = $this->getForm()->addCheckbox('__selector_all')->setHtmlAttribute('onclick', "gridSelectAll(this.closest('table'));");
		
		$columnInput = $this->addColumnInput($selectorAll->getControl(), '__selector', function ($id) {
			return (new Checkbox())->setHtmlAttribute('value', $id)->setHtmlAttribute('class', 'rowSelector');
		}, null);
		
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
		$this->template->render(__DIR__ . '/Datagrid.latte');
	}

	protected function createComponentForm(): ?IComponent
	{
		return new Form();
	}
	
	protected function createComponentFilterForm(): ?IComponent
	{
		return new FilterForm();
	}
	
	protected function createComponentPaging(): ?IComponent
	{
		return new Paging();
	}

	protected function registerInput(string $name, $defaultValue, bool $isCheckboxType = false): void
	{
		$this->inputs[$name] = [$defaultValue, $isCheckboxType];
	}
	
	/**
	 * @param string $name
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		$prefix = 'addFilter';
		$controlName = (string) \substr($name, \strlen($prefix));
		$form = $this['filterForm'];
		
		if ($prefix === \substr($name, 0, \strlen($prefix)) && \method_exists($form, 'add' . $controlName)) {
			$method = 'add' . $controlName;
			
			$this->addFilterExpression($args[1], \array_shift($args));
			
			return $form->$method(...$args);
		}
		
		/** @noinspection PhpUndefinedClassInspection */
		return parent::__call($name, $args);
	}
}
