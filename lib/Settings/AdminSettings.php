<?php
namespace OCA\Wopi\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {

	/** @var IConfig */
	private $config;

	/**
	 * Admin constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {

		$serverUrl = $this->config->getAppValue('wopi', 'serverUrl');
		$parameters = [
			'server_url' => $serverUrl
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