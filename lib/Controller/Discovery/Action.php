<?php

namespace OCA\Wopi\Controller\Discovery;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;

class Action
{
	/**
	 * @Type("string")
	 * @XmlAttribute
	 */
	public $name;

	/**
	 * @Type("string")
	 * @XmlAttribute
	 */
	public $ext;

	/**
	 * @Type("string")
	 * @XmlAttribute
	 * @SerializedName("urlsrc")
	 */
	public $urlSrc;
}