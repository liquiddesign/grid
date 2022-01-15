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
		/** @var \Nette\Forms\Controls\SelectBox $select */
		$select = $this->addSelect('onpage', null, $options);
		
		$this->onAnchor[] = function (OnPageForm $form) use ($select): void {
			/** @var \Grid\Datalist $datalist */
			$datalist = $form->lookup(Datalist::class);
			$name = $datalist->getName();
			$form->getAction()->setParameter("$name-onpage", null);
			$select->setHtmlAttribute('name', "$name-onpage");
			$select->setDefaultValue($datalist->getOnPage());
		};
		
		$this->onValidate[] = function (OnPageForm $form) use ($select): void {
			/** @var \Grid\Datalist $datalist */
			$datalist = $form->lookup(Datalist::class);

			// prepare for autoCanonization
			if ($select->getValue() !== null) {
				return;
			}
			
			$select->setDefaultValue($datalist->getOnPage());
			$select->cleanErrors();
		};
	}
}
