<?php
namespace OCA\Wopi\Controller;

use OCA\Wopi\Common\DiscoveryWorker;
use OCA\Wopi\Controller\Discovery\Action;
use OCA\Wopi\Controller\Discovery\NetZone;
use OCA\Wopi\Db\WopiToken;
use OCA\Wopi\Db\WopiTokenMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
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
	/**
	 * @var DiscoveryWorker
	 */
	private $discoveryWorker;
	/**
	 * @var IRootFolder
	 */
	private $rootFolder;

	public function __construct($AppName, IRequest $request, $UserId, ITimeFactory $timeFactory, IURLGenerator $urlGenerator,
		IConfig $config, IRootFolder $rootFolder, WopiTokenMapper $tokenMapper, DiscoveryWorker $discoveryWorker){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->urlGenerator=$urlGenerator;
		$this->tokenMapper = $tokenMapper;
		$this->timeFactory = $timeFactory;
		$this->config = $config;
		$this->discoveryWorker = $discoveryWorker;
		$this->rootFolder = $rootFolder;
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
	 * @NoCSRFRequired
	 */
	public function editor($id) {
		$this->tokenMapper->deleteOld();
		$files = $this->rootFolder->getById($id);
		if ($files === [])
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = array_shift($files);
		$edit = ($file->getPermissions() & Constants::PERMISSION_UPDATE) > 0;
		if(!$file instanceof File) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
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
		$actions = $this->discoveryWorker->getActions($file->getExtension());
		$action = null;
		/** @var Action $action */
		foreach ($actions as $act) {
			if ($act->name === 'view' || ($act->name === 'edit' && $edit))
				$action = $act;
			if ($action === 'edit')
				break;
		}
		$serverUrl = preg_replace('/<.+>/', '', $action->urlSrc);
		$url = $serverUrl . 'WOPISrc=' . $srcurl;
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

	/**
	 * @NoAdminRequired
	 */
	public function getDiscovery() {
		$result = $this->discoveryWorker->getDiscovery();
		$response = new JSONResponse($result);
		return $response;
	}
}
