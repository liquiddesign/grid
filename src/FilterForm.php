<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;

class FilterForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);
		
		$this->setMethod('get');
		
		/* @phpstan-ignore-next-line */
		$this->onAnchor[] = function (FilterForm $form): void {
			$datalist = $form->lookup(Datalist::class)->getName();
			
			/** @var \Nette\Forms\Controls\BaseControl $component */
			foreach ($form->getComponents(true, BaseControl::class) as $component) {
				$name = $component->getName();
				$form->getAction()->setParameter("$datalist-$name", null);
				
				if ($component instanceof Button) {
					$component->setHtmlAttribute('name', '');
				} else {
					$component->setHtmlAttribute('name', "$datalist-$name");
				}
			}
		};
	}
}
