<?php

namespace OCA\Wopi\Common;

class Utilities{

	public static function getGuid()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
		// @codingStandardsIgnoreStart

		return sprintf(
			'%04x%04x-%04x-%04x-%02x%02x-%04x%04x%04x',
			mt_rand(0, 65535),
			mt_rand(0, 65535),          // 32 bits for "time_low"
			mt_rand(0, 65535),          // 16 bits for "time_mid"
			mt_rand(0, 4096) + 16384,   // 16 bits for "time_hi_and_version", with
			// the most significant 4 bits being 0100
			// to indicate randomly generated version
			mt_rand(0, 64) + 128,       // 8 bits  for "clock_seq_hi", with
			// the most significant 2 bits being 10,
			// required by version 4 GUIDs.
			mt_rand(0, 256),            // 8 bits  for "clock_seq_low"
			mt_rand(0, 65535),          // 16 bits for "node 0" and "node 1"
			mt_rand(0, 65535),          // 16 bits for "node 2" and "node 3"
			mt_rand(0, 65535)           // 16 bits for "node 4" and "node 5"
		);

		// @codingStandardsIgnoreEnd
	}

	public static function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}