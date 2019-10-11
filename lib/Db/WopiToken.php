<?php

namespace OCA\Wopi\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method setUserId($userId)
 * @method setValidBy(float|int $param)
 * @method setValue(string $generateRandomString)
 * @method setFileId($id)
 * @method getValue()
 * @method getUserId()
 * @method getValidBy()
 */
class WopiToken extends Entity {

	protected $value;
	protected $userId;
	protected $fileId;
	protected $validBy;

	public function __construct() {
		$this->addType('$fileId', 'integer');
		$this->addType('id', 'string');
		$this->addType('validBy', 'integer');
	}

	public function columnToProperty($column) {
		if ($column === 'user_id') {
			return 'userId';
		} else  if ($column === 'file_id') {
			return 'fileId';
		} else if ($column === 'valid_by') {
			return 'validBy';
		} else {
			return parent::columnToProperty($column);
		}
	}

	public function propertyToColumn($property) {
		if ($property === 'userId') {
			return 'user_id';
		} else  if ($property === 'fileId') {
			return 'file_id';
		} else if ($property === 'validBy') {
			return 'valid_by';
		} else {
			return parent::propertyToColumn($property);
		}
	}
}