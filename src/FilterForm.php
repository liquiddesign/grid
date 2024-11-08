<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\InvalidArgumentException;

/**
 * Class FilterForm
 * @deprecated use Form with $grid->makeFilterForm() instead
 */
class FilterForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);
		
		$this->setMethod('get');
		
		$this->onAnchor[] = function (FilterForm $form): void {
			$datalist = $form->lookup(Datalist::class)->getName();
			
			$submit = false;

			/** @var \Nette\Forms\Controls\BaseControl $component */
			foreach ($form->getComponents(true, BaseControl::class) as $component) {
				$name = $component->getName();
				/** @phpstan-ignore-next-line */
				$form->getAction()->setParameter("$datalist-$name", null);
				
				if ($component instanceof Button) {
					if (!$submit) {
						$component->setHtmlAttribute('name', '');
						$submit = true;
					}
				} else {
					$component->setHtmlAttribute('name', "$datalist-$name");
				}
			}
		};
		
		$this->onRender[] = function (FilterForm $form): void {
			/** @var \Grid\Datalist $datalist */
			$datalist = $form->lookup(Datalist::class);
			
			foreach ($datalist->getFilters() as $filter => $value) {
				/** @var \Nette\Forms\Controls\BaseControl|null $component */
				$component = $form->getComponent($filter);
				
				if (!isset($form[$filter]) || !$component) {
					return;
				}
				
				try {
					$component->setDefaultValue($value);
				} catch (InvalidArgumentException $e) {
					// values are out of allowed set catch
				}
			}
		};
	}
}
