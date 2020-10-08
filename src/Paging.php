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
	public function render(): void
	{
		$this->template->page = $this->getParent()->getName() . '-page';
		$this->template->onpage =  $this->getParent()->getName() . '-onpage';
		$this->template->paginator =  $this->getParent()->getPaginator(true);
		
		$parentFilename = (new \ReflectionClass($this->getParent()))->getFileName();
		$filePath = \substr($parentFilename, 0 , (strrpos($parentFilename, '.'))) . '-paging.latte';
		
		$this->template->render($this->template->file ? $this->template->file : (\is_file($filePath) ? $filePath : __DIR__ . '/paging.latte'));
	}
}
