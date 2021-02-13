<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;

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
		
		/* @phpstan-ignore-next-line */
		$this->onAnchor[] = function (FilterForm $form): void {
			$datalist = $form->lookup(Datalist::class)->getName();
			
			$submit = false;
			/** @var \Nette\Forms\Controls\BaseControl $component */
			foreach ($form->getComponents(true, BaseControl::class) as $component) {
				$name = $component->getName();
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
		
		/* @phpstan-ignore-next-line */
		$this->onRender[] = function (FilterForm $form): void {
			foreach ($form->lookup(Datalist::class)->getFilters() as $filter => $value) {
				if (isset($form[$filter]) && $component = $form->getComponent($filter)) {
					/** @var \Nette\Forms\Controls\BaseControl $component */
					$component->setDefaultValue($value);
				}
			}
		};
	}
}
