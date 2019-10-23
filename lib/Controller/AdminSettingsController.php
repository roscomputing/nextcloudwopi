<?php
namespace OCA\Wopi\Controller;

use OCA\Wopi\Common\DiscoveryWorker;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;


class AdminSettingsController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IAppData
	 */
	private $appData;

	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var DiscoveryWorker
	 */
	private $discoveryWorker;

	public function __construct($AppName, IRequest $request, IConfig $config,  IAppData $appData, ILogger $logger, DiscoveryWorker $discoveryWorker){
		parent::__construct($AppName, $request);

		$this->config = $config;
		$this->appData = $appData;
		$this->logger = $logger;
		$this->discoveryWorker = $discoveryWorker;
	}

	/**
	 * @param $url
	 * @return JSONResponse
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function setUrl($url) {
		$url = rtrim($url, "/");
		$this->config->setAppValue('wopi', 'serverUrl', $url);
		$response = $this->discoveryWorker->discovery($url);
		return new JSONResponse($response);
	}

}
