<?php
namespace OCA\Wopi\Controller;

use OCA\Wopi\Db\WopiToken;
use OCA\Wopi\Db\WopiTokenMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IURLGenerator;
use OCA\Wopi\Common\Utilities;

class PageController extends Controller {

	private $userId;
	/** @var IURLGenerator */
	private $urlGenerator;
	/**
	 * @var WopiTokenMapper
	 */
	private $tokenMapper;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;
	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct($AppName, IRequest $request, $UserId, ITimeFactory $timeFactory, IURLGenerator $urlGenerator,
		IConfig $config, WopiTokenMapper $tokenMapper){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->urlGenerator=$urlGenerator;
		$this->tokenMapper = $tokenMapper;
		$this->timeFactory = $timeFactory;
		$this->config = $config;
	}

	/**
	 *
	 * @NoAdminRequired
	 */
	public function index() {
		return new TemplateResponse('wopi', 'index');  // templates/index.php
	}
	/**
	 * @NoAdminRequired
	 */
	public function editor($id) {
		$this->tokenMapper->deleteOld();
		$route = 'wopi.file.check_file_info';
        $parameters = array('id' => $id);
		$srcurl = urlencode($this->urlGenerator->linkToRouteAbsolute($route, $parameters));
		$token = new WopiToken();
		$token->setId(Utilities::getGuid());
		$token->setUserId($this->userId);
		$token->setValidBy($this->timeFactory->getTime()  + (60*60*5));
		$token->setValue(Utilities::generateRandomString(64));
		$token->setFileId($id);
		$this->tokenMapper->insert($token);
		$serverUrl = rtrim($this->config->getAppValue('wopi', 'serverUrl'), "/");
		$url = $serverUrl . '/we/wordeditorframe.aspx?WOPISrc=' . $srcurl;
		$response = new TemplateResponse('wopi', 'editor',
			array('url' => $url,
				'token' => $token->getValue(),
				'token_ttl' => $token->getValidBy() * 1000
			));  // templates/editor.php
		$response->addHeader('Cache-Control', 'no-cache, no-store');
		$response->addHeader('Expires', '-1');
		$response->addHeader('Pragma', 'no-cache');
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFormActionDomain('*');
		//$csp->addAllowedChildSrcDomain('*');
		$csp->addAllowedFrameDomain('*');
		$response->setContentSecurityPolicy($csp);
		return $response;
	}
}
