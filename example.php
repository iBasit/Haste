<?php
require_once (realpath(dirname(__FILE__).'/./include/setup.inc.php'));

$rs = new resources();
$rs->setProductionMode(FALSE);

$rs->setPath(array(
	'pages' => array(
		'dir' => realpath(App::$root),
		'type' => 'page',
		'filter' => 'static_resources'
	),
	'html' => array(
		'dir' => realpath(App::$root.'/./../themes'),
		'type' => 'template'
	),
	'js-images' => array(
		'dir' => realpath(App::$root.'/static_resources'),
		'type' => 'static',
		'url_path' => App::$domain_url.'/static_resources'
	)
));

$rs->scan();
$rs->build();

print_r($rs->getTree());


?>