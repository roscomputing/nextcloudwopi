<?php

namespace OCA\Wopi\AppInfo;

use OCA\Wopi\Hooks\WopiLockHooks;
use OCP\AppFramework\App;
use OCP\IServerContainer;
use OCP\Util;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('wopi', $urlParams);

		$container = $this->getContainer();
		/** @var IServerContainer $server */
		$server = $container->getServer();
		/*$container->registerService('WopiLockHooks', function(SimpleContainer $c) use ($server) {
			return new WopiLockHooks($server->getRootFolder(),
			$c->query(ITimeFactory::class),
			$c->query(WopiLockMapper::class));
		});
		$container->registerService('WopiTokenMapper', function (SimpleContainer $c) use ($server) {
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
		$container = $this->getContainer();
		$server = $container->getServer();

		/** @var EventDispatcherInterface $dispatcher */
		$dispatcher = $server->getEventDispatcher();

		$serverUrl = $server->getConfig()->getAppValue('wopi', 'serverUrl');
		if (strlen($serverUrl) > 0)
		{
			$dispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
				Util::addScript('wopi', 'fileshook');
			});
		}
		$container->query(WopiLockHooks::class)->register();
	}
}
