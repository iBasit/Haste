<?php
require_once (realpath(dirname(__FILE__).'/./include/setup.inc.php'));

$rs = new resources();
$rs->setPath(array(
	'html' => array(
		'dir' => realpath(site::$root.'/./wLib/themes')
	),
	'js' => array(
		'dir' => realpath(site::$root.'/javascript'),
		'url_path' => site::$domain_url.'/javascript'
	),
	'css' => array(
		'dir' => realpath(site::$root.'/images'),
		'url_path' => site::$domain_url.'/images'
	)
));
$tree = $rs->scan();
print_r($tree);


?>
