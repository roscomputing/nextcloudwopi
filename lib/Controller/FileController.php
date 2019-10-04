<?php
namespace OCA\Wopi\Controller;

use OCA\Wopi\Db\WopiTokenMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http;
use OCP\IUserManager;

class FileController extends Controller {

	/** @var IRootFolder */
	protected $rootFolder;
	/**
	 * @var WopiTokenMapper
	 */
	private $tokenMapper;
	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, IUserManager $userManager, WopiTokenMapper $tokenMapper){
		parent::__construct($AppName, $request);
		$this->rootFolder = $rootFolder;
		$this->tokenMapper = $tokenMapper;
		$this->userManager = $userManager;
	}

	/**
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 */
	public function getinfo($id,$access_token) {
		$token = null;
		$user = null;
		try{
			$token=$this->tokenMapper->find($access_token);
			$user = $this->userManager->get($token->getUserId());
		}catch (DoesNotExistException $e){
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$file = null;
        try {
			$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
			if ($files === [])
				return new DataResponse([], Http::STATUS_NOT_FOUND);
			$file = array_shift($files);
        } catch(\OCP\Files\NotFoundException $e) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }
		if($file instanceof \OCP\Files\File) {
			$response = [ 'BaseFileName' => $file->getName(),
				'OwnerId' => $file->getOwner()->getUID(),
				'Size' => $file->getSize(),
				'SHA256' => $file->getChecksum(),
				'Version' => (string)$file->getMTime(),
				'SupportsUpdate' => true,
				'UserCanWrite' => true,
				'SupportsLocks' => true,
				'UserCanNotWriteRelative' => true];
			return new JSONResponse($response);
		} else {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
	/**
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 */
	public function get($id,$access_token) {
		$token = null;
		$user = null;
		try{
			$token=$this->tokenMapper->find($access_token);
			$user = $this->userManager->get($token->getUserId());
		}catch (DoesNotExistException $e){
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$file = null;
		try {
			$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
			if ($files === [])
				return new DataResponse([], Http::STATUS_NOT_FOUND);
			$file = array_shift($files);
		} catch(\OCP\Files\NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if($file instanceof \OCP\Files\File) {
			return new FileDisplayResponse($file);
		} else {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
