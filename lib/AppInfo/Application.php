<?php

namespace OCA\Wopi\AppInfo;

use mysql_xdevapi\Exception;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Wopi\Db\WopiTokenMapper;
use OCA\Wopi\Listener\LoadAdditionalScripts;
use OCP\AppFramework\App;
use OC\AppFramework\Utility\SimpleContainer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IServerContainer;
use OCA\Wopi\Controller\FileController;
use OCA\Wopi\Controller\PageController;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('wopi', $urlParams);

		$container = $this->getContainer();
		/** @var IServerContainer $server */
		$server = $container->getServer();


		/*$container->registerService('WopiTokenMapper', function (SimpleContainer $c) use ($server) {
			$t3=$server->getDatabaseConnection();
			$t4=$server->getDatabaseConnection();
			$t4=new WopiTokenMapper($server->getDatabaseConnection());
			return $t4;

		});
		*
		 * Controllers

		$container->registerService('FileController', function (SimpleContainer $c) use ($server) {
			return new FileController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->getRootFolder()
			);
		});
		$container->registerService('PageController', function (SimpleContainer $c) use ($server) {
			$t1=$c->query('UserId');
			$t2=$server->getURLGenerator();
			$t3=$c->query("WopiTokenMapper");


			return new PageController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->query('UserId'),
				$server->getURLGenerator(),
				$c->query("WopiTokenMapper")
			);
		});*/

	}

	public function register(){
		$server = $this->getContainer()->getServer();

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $server->query(IEventDispatcher::class);

		$this->registerSidebarScripts($dispatcher);
	}

	protected function registerSidebarScripts(IEventDispatcher $dispatcher) {
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScripts::class);
	}
}
