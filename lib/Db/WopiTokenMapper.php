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


	public function findOld($validBy, $limit=null, $offset=null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('wopi_tokens')
			->where($qb->expr()->lt('valid_by', $qb->createNamedParameter($validBy, IQueryBuilder::PARAM_INT)))
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}



}