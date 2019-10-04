<?php
namespace OCA\Wopi\Controller;

use OCA\Wopi\Db\WopiToken;
use OCA\Wopi\Db\WopiTokenMapper;
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

	public function __construct($AppName, IRequest $request, $UserId, IURLGenerator $urlGenerator,
		WopiTokenMapper $tokenMapper){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->urlGenerator=$urlGenerator;
		$this->tokenMapper = $tokenMapper;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse('wopi', 'index');  // templates/index.php
	}

	public function editor($id) {
		$route = 'wopi.file.getinfo';
        $parameters = array('id' => $id);
		$srcurl = urlencode($this->urlGenerator->linkToRouteAbsolute($route, $parameters));


		$token = new WopiToken();
		$token->setId(Utilities::getGuid());
		$token->setUserId($this->userId);
		$token->setValidBy(time() + (60*30));
		$token->setValue(Utilities::generateRandomString(64));
		$token->setFileId($id);
		$this->tokenMapper->insert($token);
		$url = 'https://officeserver/we/wordeditorframe.aspx?WOPISrc=' . $srcurl;
		$response = new TemplateResponse('wopi', 'editor',array('url' => $url, 'token' => $token->getValue()));  // templates/editor.php
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
