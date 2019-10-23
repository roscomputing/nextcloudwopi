<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Wopi\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#editor', 'url' => '/editor', 'verb' => 'POST'],
		['name' => 'page#get_discovery', 'url' => '/getdiscovery', 'verb' => 'GET'],
       ['name' => 'file#check_file_info', 'url' => '/files/{id}', 'verb' => 'GET'],
		['name' => 'file#lock', 'url' => '/files/{id}', 'verb' => 'POST'],
       ['name' => 'file#get_file', 'url' => '/files/{id}/contents', 'verb' => 'GET'],
		['name' => 'file#put_file', 'url' => '/files/{id}/contents', 'verb' => 'POST'],
		['name' => 'admin_settings#set_url', 'url' => '/admin/seturl', 'verb' => 'POST'],
    ]
];
