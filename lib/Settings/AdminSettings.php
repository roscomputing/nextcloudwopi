<?php
namespace OCA\Wopi\Settings;

use OCA\Wopi\Common\DiscoveryWorker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {

	/** @var IConfig */
	private $config;
	/**
	 * @var DiscoveryWorker
	 */
	private $discoveryWorker;

	/**
	 * Admin constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config, DiscoveryWorker $discoveryWorker) {
		$this->config = $config;
		$this->discoveryWorker = $discoveryWorker;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {

		$serverUrl = $this->config->getAppValue('wopi', 'serverUrl');
		$result = $this->discoveryWorker->getDiscovery();
		$parameters = [
			'server_url' => $serverUrl,
			'time' => !empty($result->time) ? new \DateTime('@' . $result->time) : null,
			'ttl' => !empty($result->ttl) ? new \DateTime('@' . $result->ttl) : null,
			'extensions' =>$result->extensions,
			'text'=>$result->text
		];
		return new TemplateResponse('wopi', 'admin', $parameters);
	}

	/**
	 * @return string
	 */
	public function getSection() {
		return 'additional';
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return 50;
	}

}