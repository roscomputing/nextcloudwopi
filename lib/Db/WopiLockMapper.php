<?php

namespace OCA\Wopi\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class WopiLockMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'wopi_locks');
	}

	/**
	 * @param int $fileId
	 * @return WopiLock
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $fileId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_locks')
			->where(
				$qb->expr()->eq('fileId', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}


	/**
	 * @param int $validBy
	 * @param int $limit
	 * @param int $offset
	 * @return array|WopiLock[]
	 */
	public function findOld($validBy, $limit=null, $offset=null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_locks')
			->where($qb->expr()->lt('validBy', $qb->createNamedParameter($validBy, IQueryBuilder::PARAM_INT)))
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}


}