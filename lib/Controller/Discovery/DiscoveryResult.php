<?php
namespace OCA\Wopi\Controller\Discovery;

use JMS\Serializer\Annotation as Serializer;

class DiscoveryResult{
	/** @var int
	 *@Serializer\Type("int")
	 */
	public $time;
	/** @var int
	 * @Serializer\Type("int")
	 */
	public $ttl;
	/** @var string
	 * @Serializer\Type("string")
	 */
	public $text;
	/** @var bool
	 * @Serializer\Type("bool")
	 */
	public $success;
	/** @var string
	 * @Serializer\Type("string")
	 */
	public $extensions;
}