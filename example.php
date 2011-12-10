<?php
require_once (realpath(dirname(__FILE__).'/./include/setup.inc.php'));

$rs = new resources();
$rs->setPath(array(
	'html' => array(
		'dir' => realpath(App::$root.'/./wfiles/themes')
	),
	'js' => array(
		'dir' => realpath(App::$root.'/javascript'),
		'url_path' => App::$domain_url.'/javascript'
	),
	'css' => array(
		'dir' => realpath(App::$root.'/images'),
		'url_path' => App::$domain_url.'/images'
	)
));
$tree = $rs->scan();
print_r($tree);


?>