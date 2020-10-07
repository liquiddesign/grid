<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;

class OnPageForm extends Form
{
	public function __construct(array $options)
	{
		parent::__construct();
		
		$this->setMethod('get');
		$this->addSelect('onpage', null, $options);
		
		/* @phpstan-ignore-next-line */
		$this->onAnchor[] = function (OnpageForm $form): void {
			$datalist = $form->lookup(Datalist::class);
			$name = $datalist->getName();
			$form->getAction()->setParameter("$name-onpage", null);
			$form['onpage']->setHtmlAttribute('name', "$name-onpage");
			$form['onpage']->setDefaultValue($datalist->getOnPage());
		};
		
		$this->onValidate[] = function (OnpageForm $form) {
			$datalist = $form->lookup(Datalist::class);
			// prepare for autoCanonization
			if ($form['onpage']->getValue() === null) {
				$form['onpage']->setDefaultValue($datalist->getOnPage());
				$form['onpage']->cleanErrors();
			}
		};
	}
}
