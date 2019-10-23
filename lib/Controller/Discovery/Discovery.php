<?php

namespace OCA\Wopi\Controller\Discovery;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("wopi-discovery")
 */
class Discovery
{
	/**
	 * @Type("array<OCA\Wopi\Controller\Discovery\NetZone>")
	 * @XmlList(inline=true, entry="net-zone")
	 * @var array<NetZone>
	 */
	public $netZones;
}