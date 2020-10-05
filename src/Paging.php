<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Control;

/**
 * Class Paging
 * @property \Nette\Bridges\ApplicationLatte\Template|\StdClass $template
 */
class Paging extends Control
{
	public function render(Datalist $list): void
	{
		$this->template->page = $list->getName() . '-page';
		$this->template->onpage = $list->getName() . '-onpage';
		$this->template->paginator = $list->getPaginator(true);
		$this->template->render(__DIR__ . '/Paging.latte');
	}
}
