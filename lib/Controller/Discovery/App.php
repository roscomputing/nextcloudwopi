<?php

namespace OCA\Wopi\Controller\Discovery;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlList;

class App
{
	/**
	 * @Type("string")
	 * @XmlAttribute
	 */
	public $name;

	/**
	 * @Type("string")
	 * @SerializedName("favIconUrl")
	 * @XmlAttribute
	 */
	public $favIconUrl;

	/**
	 * @Type("array<OCA\Wopi\Controller\Discovery\Action>")
	 * @XmlList(inline=true, entry="action")
	 * @var array<Action>
	 */
	public $actions;
}