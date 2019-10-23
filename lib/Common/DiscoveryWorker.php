<?php
namespace OCA\Wopi\Common;

use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use OCA\Wopi\Controller\Discovery\Action;
use OCA\Wopi\Controller\Discovery\App;
use OCA\Wopi\Controller\Discovery\Discovery;
use OCA\Wopi\Controller\Discovery\DiscoveryResult;
use OCA\Wopi\Controller\Discovery\NetZone;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


class DiscoveryWorker {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IAppData
	 */
	private $appData;

	const DISCOVERYFILE = 'discovery.xml';

	const DISCOVERYRESULTFILE = 'discovery.json';

	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;
	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	/**
	 * @var IL10N
	 */
	private $lang;

	public function __construct(IConfig $config, IAppData $appData, ILogger $logger, ITimeFactory $timeFactory, IL10N $lang){

		$this->config = $config;
		$this->appData = $appData;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
		$this->lang = $lang;
		$this->serializer = SerializerBuilder::create()->build();
		AnnotationRegistry::registerLoader('class_exists');
	}

	/**
	 * @param $url
	 * @return DiscoveryResult
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function discovery($url) {
		$client = HttpClient::create();
		$discoveryUrl = $url . '/hosting/discovery';
		$success = false;
		$text = '';
		$extensions = array();
		try {
			$discoveryFile = $this->getFile(self::DISCOVERYFILE);
			$response = $client->request('GET', $discoveryUrl, ['max_duration' => 30]);
			$statusCode = $response->getStatusCode();
			if ($statusCode === 200){
				$contentType = $response->getHeaders()['content-type'][0];
				$content = $response->getContent();
				/** @var Discovery $discovery */
				$discovery = $this->serializer->deserialize($content,Discovery::class, 'xml');

				$zone = null;
				/** @var NetZone $netZone */
				foreach ($discovery->netZones as $netZone) {
					$zone = $netZone;
					if (strpos($netZone->name, 'external-https') !== false)
						break;
				}
				if (!empty($zone)){
					/** @var App $app */
					foreach ($zone->apps as $app) {
						/** @var Action $action */
						foreach ($app->actions as $action) {
							if (($action->name === 'view' || $action->name === 'edit') &&
								!empty($action->ext) && !in_array($action->ext, $extensions))
								$extensions[] = $action->ext;
						}
					}
					$discoveryFile->putContent($content);
					$success = true;
				}
				else
					$text = 'No net-zones received in discovery information from ' . $discoveryUrl;
			}
			else
				$text = 'Invalid status code ' . $statusCode . ' received while requesting discovery information from ' . $discoveryUrl;
		} catch (TransportExceptionInterface $e) {
			$text = 'Transport exception occurred while requesting discovery information from ' . $discoveryUrl;
		} catch (ClientExceptionInterface $e) {
			$text = 'Client exception occurred while requesting discovery information from ' . $discoveryUrl;
		} catch (RedirectionExceptionInterface $e) {
			$text = 'Redirection exception occurred while requesting discovery information from ' . $discoveryUrl;
		} catch (ServerExceptionInterface $e) {
			$text = 'Redirection exception occurred while requesting discovery information from ' . $discoveryUrl;
		} catch (\Exception $e){
			$text = 'Unknown exception occurred while requesting discovery information from ' . $discoveryUrl;
			$this->logger->logException($e);
		}
		if ($success)
			$text = 'Success';
		$response = $this->getDiscovery(false);
		$response->success=$success;
		$response->text = $this->lang->t($text);
		if ($success)
			$response->extensions = implode(',',$extensions);
		$response->time=$this->timeFactory->getTime();
		$response->ttl = $success ? $response->time + 60*60*24 : $response->time + 60*60;
		$this->saveDiscovery($response);
		return $response;
	}

	/**
	 * @param string $ext
	 * @return array|Action[]
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getActions(string $ext){
		$discoveryFile = $this->getFile(self::DISCOVERYFILE);
		$content = $discoveryFile->getContent();
		/** @var Discovery $discovery */
		$discovery = $this->serializer->deserialize($content,Discovery::class, 'xml');

		$zone = null;
		/** @var NetZone $netZone */
		foreach ($discovery->netZones as $netZone) {
			$zone = $netZone;
			if (strpos($netZone->name, 'external-https') !== false)
				break;
		}
		$result = array();
		/** @var App $app */
		foreach ($zone->apps as $app) {
			/** @var Action $action */
			foreach ($app->actions as $action) {
				if (($action->name === 'view' || $action->name === 'edit') &&
					$action->ext === $ext)
					$result[] = $action;
			}
		}
		return $result;
	}

	/**
	 * @param bool $update
	 * @return DiscoveryResult
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getDiscovery(bool $update = true){
		$file = $this->getFile(self::DISCOVERYRESULTFILE);
		$json = $file->getContent();
		$result = null;
		if (!empty($json))
		{
			try{
				/**@var DiscoveryResult $result */
				$result = $this->serializer->deserialize($json, DiscoveryResult::class, 'json');
				if ($result->ttl < $this->timeFactory->getTime() || empty($result->extensions))
					$result = null;
			}
			catch (\Exception $e){
				$file->putContent('');
				$this->logger->logException($e);
			}
		}
		if (empty($result) && $update)
		{
			$url = $this->config->getAppValue('wopi', 'serverUrl');
			if (!empty($url))
				$result = $this->discovery($url);
		}
		if (empty($result))
			$result = new DiscoveryResult();
		return $result;
	}

	/**
	 * @param DiscoveryResult $discoveryResult
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	private function saveDiscovery(DiscoveryResult $discoveryResult){
		$file = $this->getFile(self::DISCOVERYRESULTFILE);
		$json = $this->serializer->serialize($discoveryResult, 'json');
		$file->putContent($json);
	}

	/**
	 * @param string $name
	 * @return \OCP\Files\SimpleFS\ISimpleFile
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	private function getFile(string $name){
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
		$file = $folder->fileExists($name) ? $folder->getFile($name) : $folder->newFile($name);
		return $file;
	}
}