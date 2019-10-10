<?php
declare(strict_types=1);

namespace OCA\Wopi\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Util;

class LoadAdditionalScripts implements IEventListener {

	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}
		$serverUrl = $this->config->getAppValue('wopi', 'serverUrl');
		if (strlen($serverUrl) > 0)
			Util::addScript('wopi', 'fileshook');
	}

}
