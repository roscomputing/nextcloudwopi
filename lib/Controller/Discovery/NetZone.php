<?php

namespace OCA\Wopi\Controller\Discovery;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlList;

class NetZone
{
	/**
	 * @Type("string")
	 * @XmlAttribute
	 */
	public $name;

	/**
	 * @Type("array<OCA\Wopi\Controller\Discovery\App>")
	 * @XmlList(inline=true, entry="app")
	 * @var array<App>
	 */
	public $apps;
}