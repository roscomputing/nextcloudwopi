<?php
namespace OCA\Wopi\Controller;

use Exception;
use OCA\Wopi\Common\Utilities;
use OCA\Wopi\Db\WopiLock;
use OCA\Wopi\Db\WopiLockMapper;
use OCA\Wopi\Db\WopiTokenMapper;
use OCA\Wopi\Hooks\WopiLockHooks;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IAppData;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http;
use OCP\IUserManager;
use OCP\Lock\LockedException;

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
	/**
	 * @var WopiLockMapper
	 */
	private $lockMapper;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;
	/**
	 * @var WopiLockHooks
	 */
	private $lockHooks;
	/**
	 * @var IAppData
	 */
	private $appData;
	/**
	 * @var ILogger
	 */
	private $logger;

	public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, IUserManager $userManager,
								ITimeFactory $timeFactory, IAppData $appData, ILogger $logger, WopiTokenMapper $tokenMapper, WopiLockMapper $lockMapper,
							WopiLockHooks $lockHooks){
		parent::__construct($AppName, $request);
		$this->rootFolder = $rootFolder;
		$this->tokenMapper = $tokenMapper;
		$this->userManager = $userManager;
		$this->lockMapper = $lockMapper;
		$this->timeFactory = $timeFactory;
		$this->lockHooks = $lockHooks;
		$this->appData = $appData;
		$this->logger = $logger;
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 * @param $id
	 * @param $access_token
	 * @return DataResponse|JSONResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function checkFileInfo($id,$access_token) {
		$token=$this->tokenMapper->find($access_token);
		if (empty($token))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$user = $this->userManager->get($token->getUserId());
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$file = null;
		$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
		if ($files === [])
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = array_shift($files);
		if(!$file instanceof File) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$response = ['BaseFileName' => $file->getName(),
			'OwnerId' => $file->getOwner()->getUID(),
			'Size' => $file->getSize(),
			'SHA256' => $file->getChecksum(),
			'Version' => (string)$file->getMTime(),
			'SupportsUpdate' => $file->isUpdateable(),
			'UserCanWrite' => ($file->getPermissions() & Constants::PERMISSION_UPDATE) > 0,
			'SupportsLocks' => true,
			'UserCanNotWriteRelative' => true,
			'SupportsUserInfo' => false,
			'SupportsExtendedLockLength' => true,
			'UserFriendlyName' => $user->getDisplayName()];
		return new JSONResponse($response);
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 * @param $id
	 * @param $access_token
	 * @return DataResponse|StreamResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getFile($id,$access_token) {
		$token=$this->tokenMapper->find($access_token);
		if (empty($token))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$user = $this->userManager->get($token->getUserId());
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
		if ($files === [])
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = array_shift($files);
		if(!($file instanceof File && ($file->getPermissions() & Constants::PERMISSION_READ) > 0)) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$maxSize = $this->request->getHeader('X-WOPI-MaxExpectedSize');
		if (empty($maxSize))
			$maxSize = PHP_INT_MAX;
		else
			$maxSize = intval($maxSize);
		if ($file->getSize() > $maxSize)
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		$oFile = $file->fopen('r');
		$response = new StreamResponse($oFile);
		$response->addHeader('Content-Type', 'application/octet-stream');
		$response->addHeader('X-WOPI-ItemVersion', $file->getMTime());
		return $response;
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 * @param $id
	 * @param $access_token
	 * @return DataResponse|FileDisplayResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function lock($id,$access_token) {
		$token=$this->tokenMapper->find($access_token);
		if (empty($token))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$user = $this->userManager->get($token->getUserId());
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
		if ($files === [])
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = array_shift($files);
		if(!($file instanceof File && ($file->getPermissions() & Constants::PERMISSION_UPDATE) > 0)) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$lck = $this->request->getHeader('X-WOPI-Lock');
		$wover = $this->request->getHeader('X-WOPI-Override');
		if (strlen($lck) === 0 && $wover !== "GET_LOCK" && strpos($wover,"LOCK") !== false)
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		$result = new DataResponse([], Http::STATUS_NOT_IMPLEMENTED);
		switch ($wover)
		{
			case "LOCK":
				$oldLck = $this->request->getHeader('X-WOPI-OldLock');
				if (strlen($oldLck) > 0)
				{
					$fLock = $this->lockMapper->find($id);
					if (!empty($fLock))
					{
						if ($fLock->getValue() !== $oldLck)
						{
							$result->setStatus(Http::STATUS_CONFLICT);
							$result->addHeader('X-WOPI-Lock', $fLock->getValue());
							break;
						}
						$this->lockMapper->delete($fLock);
					}
					else
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', '');
						break;
					}
				}
				$fLock = $this->lockMapper->find($id);
				if (!empty($fLock))
				{
					if ($fLock->getValue() !== $lck)
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', $fLock->getValue());
						break;
					}
					$result->setStatus(Http::STATUS_OK);
					break;
				}
				else
				{
					$newLock = new WopiLock();
					$newLock->setId(Utilities::getGuid());
					$newLock->setUserId($user->getUID());
					$newLock->setValidBy($this->timeFactory->getTime() + (60*30));
					$newLock->setValue($lck);
					$newLock->setFileId($id);
					$newLock->setTokenId($token->getId());
					$this->lockMapper->insert($newLock);
					$result->setStatus(Http::STATUS_OK);
				}
				break;
			case "GET_LOCK":
				$fLock = $this->lockMapper->find($id);
				$result->setStatus(Http::STATUS_OK);
				$result->addHeader('X-WOPI-Lock', empty($fLock) ? '' : $fLock->getValue());
				break;
			case "REFRESH_LOCK":
				$fLock = $this->lockMapper->find($id);
				if (!empty($fLock))
				{
					if ($fLock->getValue() !== $lck)
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', $fLock->getValue());
						break;
					}
					$fLock->setValidBy($this->timeFactory->getTime() + 60*30);
					$this->lockMapper->update($fLock);
					$result->setStatus(Http::STATUS_OK);
					break;
				}
				else
				{
					$result->setStatus(Http::STATUS_CONFLICT);
					$result->addHeader('X-WOPI-Lock', '');
				}
				break;
			case "UNLOCK":
				$fLock = $this->lockMapper->find($id);
				if (!empty($fLock))
				{
					if ($fLock->getValue() !== $lck)
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', $fLock->getValue());
						break;
					}
					$this->lockMapper->delete($fLock);
					$result->setStatus(Http::STATUS_OK);
				}
				else
				{
					$result->setStatus(Http::STATUS_CONFLICT);
					$result->addHeader('X-WOPI-Lock', '');
				}
				break;
		}
		if ($result->getStatus() !== Http::STATUS_NOT_IMPLEMENTED)
			$result->addHeader('X-WOPI-ItemVersion', $file->getMTime());
		return $result;
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 * @param $id
	 * @param $access_token
	 * @return DataResponse|FileDisplayResponse
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function putFile($id,$access_token) {
		$token=$this->tokenMapper->find($access_token);
		if (empty($token))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$user = $this->userManager->get($token->getUserId());
		if (empty($user))
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		$files = $this->rootFolder->getUserFolder($user->getUID())->getById($id);
		if ($files === [])
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = array_shift($files);
		if(!($file instanceof File && ($file->getPermissions() & Constants::PERMISSION_UPDATE) > 0)) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$lck = $this->request->getHeader('X-WOPI-Lock');
		$lckType = $this->request->getHeader('X-WOPI-Override');
		$result = new DataResponse([], Http::STATUS_OK);
		$fLock = $this->lockMapper->find($id);
		switch ($lckType)
		{
			case "PUT":
				if (!empty($fLock))
				{
					if ($fLock->getValue() !== $lck)
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', $fLock->getValue());
						break;
					}
				}
				else
				{
					if ($file->getSize() > 0)
					{
						$result->setStatus(Http::STATUS_CONFLICT);
						$result->addHeader('X-WOPI-Lock', '');
						break;
					}
				}
				break;

			default:
				$result->setStatus(Http::STATUS_NOT_IMPLEMENTED);
				break;
		}
		if ($result->getStatus() !== Http::STATUS_OK)
			return $result;
		$content = fopen('php://input', 'rb');
		//after pull request
		//$content=$this->request->post;
		if (empty($content)){
			$result->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			return $result;
		}
		$backup = null;
		try {
			$appFolders = $this->appData->getDirectoryListing();
			$folder = null;
			foreach($appFolders as $f) {
				if ('temp' == $f->getName()) {
					$folder = $f;
					break;
				}
			}
			if (empty($folder))
				$folder = $this->appData->newFolder('temp');
			$backupName = $file->getName() . $this->timeFactory->getTime() . 'wopiback';
			$backup = $folder->newFile($backupName);
		} catch (Exception $e) {
			$this->logger->logException($e);
		}
		$cleanCallback = function()use($content,$backup){
			try {
				if (is_resource($content))
					fclose($content);
				if (!empty($backup))
					$backup->delete();
			} catch (Exception $e) {
				$this->logger->logException($e);
			}
		};
		if (!empty($backup)){
			try{
				$backup->putContent($content);
				fclose($content);
			} catch (NotFoundException $e) {
				$this->logger->logException($e);
			} catch (NotPermittedException $e) {
				$this->logger->logException($e);
			} catch (Exception $e) {
				$this->logger->logException($e);
				$result->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		}
		if (!empty($backup) && $result->getStatus() === Http::STATUS_OK){
			try{
				$content = $backup->read();
			} catch (Exception $e) {
				$this->logger->logException($e);
				$result->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		}
		if ($result->getStatus() !== Http::STATUS_OK) {
			$cleanCallback();
			return $result;
		}
		try {
			$this->lockHooks->setLockBypass(true);
			$file->putContent($content);
			$result->addHeader('X-WOPI-ItemVersion', $file->getMTime());
		} catch (GenericFileException $e) {
			$result->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (NotPermittedException $e) {
			$result->setStatus(Http::STATUS_UNAUTHORIZED);
		} catch (LockedException $e) {
			$result->setStatus(Http::STATUS_CONFLICT);
			$result->addHeader('X-WOPI-Lock', '');
		}
		finally{
			$cleanCallback();
		}
		return $result;
	}
}
