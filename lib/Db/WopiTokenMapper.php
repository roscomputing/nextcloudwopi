<?php

namespace OCA\Wopi\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WopiTokenMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'wopi_tokens');
	}

	/**
	 * @param string $value
	 * @return WopiToken
	 * @throws DoesNotExistException if not found
	 * @throws MultipleObjectsReturnedException if more than one result
	 */
	public function find(string $value) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_tokens')
			->where(
				$qb->expr()->eq('value', $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}


	public function findOld($validBy, $limit=null, $offset=null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_tokens')
			->where($qb->expr()->lt('validBy', $qb->createNamedParameter($validBy, IQueryBuilder::PARAM_INT)))
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}



}