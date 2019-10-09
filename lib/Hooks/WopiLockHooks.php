<?php

namespace OCA\Wopi\Hooks;

use OCA\Wopi\Common\Utilities;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCA\Wopi\Db\WopiLockMapper;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Lock\ILockingProvider;

class WopiLockHooks {

	private $rootFolder;
	/**
	 * @var WopiLockMapper
	 */
	private $lockMapper;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;

	/**
	 * @var bool
	 */
	private $lockBypass;

	public function __construct(IRootFolder $rootFolder, ITimeFactory $timeFactory, WopiLockMapper $lockMapper) {
		$this->rootFolder = $rootFolder;
		$this->lockMapper = $lockMapper;
		$this->timeFactory = $timeFactory;
	}

	public function register() {
		$lockBypass = $this->lockBypass;
		$callback = function (Node $node) use (&$lockBypass) {
			if ($node instanceof File) {
				$lock = $this->lockMapper->find($node->getId());
				if (empty($lock))
					return;
				if ($lock->getValidBy() < $this->timeFactory->getTime())
				{
					$this->lockMapper->delete($lock);
					return;
				}
				if (!$lockBypass)
					$node->lock(ILockingProvider::LOCK_SHARED);
			}
		};
		$this->rootFolder->listen('\OC\Files', 'preWrite', [$this, 'preWrite']);
	}

	public function preWrite(Node $node) {
		if ($node instanceof File) {
			$lock = $this->lockMapper->find($node->getId());
			if (empty($lock))
				return;
			if ($lock->getValidBy() < $this->timeFactory->getTime())
			{
				$this->lockMapper->delete($lock);
				return;
			}
			if (!$this->lockBypass)
				$node->lock(ILockingProvider::LOCK_SHARED);
		}
	}

	/**
	 * @param bool $lockBypass
	 */
	public function setLockBypass($lockBypass): void {
		$this->lockBypass = $lockBypass;
	}
}