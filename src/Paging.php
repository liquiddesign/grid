<?php

declare(strict_types=1);

namespace Grid;

use Nette\Application\UI\Control;
use Nette\InvalidStateException;

/**
 * Class Paging
 * @property \Nette\Bridges\ApplicationLatte\Template|\StdClass $template
 */
class Paging extends Control
{
	public function getDatalist(): Datalist
	{
		$parent = $this->getParent();
		
		if (!$parent instanceof Datalist) {
			throw new InvalidStateException('Paging is not attached to Datalist');
		}
		
		return $parent;
	}
	
	public function render(): void
	{
		$this->template->page = $this->getDatalist()->getName() . '-page';
		$this->template->onpage = $this->getDatalist()->getName() . '-onpage';
		$this->template->paginator = $this->getDatalist()->getPaginator(true);
		
		$parentFilename = (new \ReflectionClass($this->getDatalist()))->getFileName();
		$filePath = \substr($parentFilename, 0, \strrpos($parentFilename, '.')) . '-paging.latte';
		
		
		$this->template->render($this->template->getFile() ?? (\is_file($filePath) ? $filePath : __DIR__ . '/paging.latte'));
	}
}
