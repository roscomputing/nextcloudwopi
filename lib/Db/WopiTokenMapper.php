<?php

namespace OCA\Wopi\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WopiTokenMapper extends QBMapper {

	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;

	public function __construct(IDBConnection $db, ITimeFactory $timeFactory) {
		parent::__construct($db, 'wopi_tokens');
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $value
	 * @return WopiToken
	 */
	public function find(string $value) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_tokens')
			->where(
				$qb->expr()->eq('value', $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR))
			);
		$items = $this->findEntities($qb);
		$result = array_shift($items);
		return $result;
	}


	public function deleteOld() {
		$validBy = $this->timeFactory->getTime();
		$qb = $this->db->getQueryBuilder();
		$qb->delete('wopi_tokens')
			->where($qb->expr()->lt('valid_by', $qb->createNamedParameter($validBy, IQueryBuilder::PARAM_INT)))
			->execute();
	}



}