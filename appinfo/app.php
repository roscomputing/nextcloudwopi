<?php

use OCA\Wopi\AppInfo\Application;

$application = new Application();
$application->register();
//$eventDispatcher = \OC::$server->getEventDispatcher();
//$eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
  //script('wopi', 'fileshook');  // adds js/script.js
  //vendor_script('myapp', 'script');  //  adds vendor/script.js
//});

