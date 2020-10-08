<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Form;

class OrderForm extends Form
{
	public function __construct(array $options)
	{
		parent::__construct();
		
		$this->setMethod('get');
		$this->addSelect('order', null, $options);
		
		/* @phpstan-ignore-next-line */
		$this->onAnchor[] = function (OrderForm $form): void {
			$datalist = $form->lookup(Datalist::class);
			$name = $datalist->getName();
			$form->getAction()->setParameter("$name-order", null);
			$form['order']->setHtmlAttribute('name', "$name-order");
			$form['order']->setDefaultValue($datalist->getOrderParameter());
		};
		
		$this->onValidate[] = function (OrderForm $form) {
			$datalist = $form->lookup(Datalist::class);
			// prepare for autoCanonization
			if ($form['order']->getValue() === null) {
				$form['order']->setDefaultValue($datalist->getOrderParameter());
				$form['order']->cleanErrors();
			}
		};
	}
}
