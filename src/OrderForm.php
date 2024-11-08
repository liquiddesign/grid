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
		/** @var \Nette\Forms\Controls\SelectBox $select */
		$select = $this->addSelect('order', null, $options);
		
		$this->onAnchor[] = function (OrderForm $form) use ($select): void {
			/** @var \Grid\Datalist $datalist */
			$datalist = $form->lookup(Datalist::class);
			$name = $datalist->getName();
			/** @phpstan-ignore-next-line */
			$form->getAction()->setParameter("$name-order", null);
			$select->setHtmlAttribute('name', "$name-order");
			$select->setDefaultValue($datalist->getOrderParameter());
		};
		
		$this->onValidate[] = function (OrderForm $form) use ($select): void {
			/** @var \Grid\Datalist $datalist */
			$datalist = $form->lookup(Datalist::class);

			// prepare for autoCanonization
			if ($select->getValue() !== null) {
				return;
			}
			
			$select->setDefaultValue($datalist->getOrderParameter());
			$select->cleanErrors();
		};
	}
}
