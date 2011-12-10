<?php
define('DEBUG', TRUE);		// debug mode

error_reporting(E_ALL);
$this_dir = dirname(__FILE__);
require_once('App.class.php');       // site app include
require_once('Log.class.php');  	 // error include
require_once('_Error.class.php'); 	 // error include
require_once('FirePHP.class.php'); 	 // error include
require_once('Database.class.php');  // db include
require_once('resources.class.php'); // resources
require_once('PhpClosure.class.php'); // google phpClosure lib

$database = array(
	"MySql",		// Type of Database.
	"localhost",	// Database Host.
	"root",			// Database Username.
	"",				// Database Password.
	"haste"			// Database Name.
	);

App::set_url(array(
	'APP_PATH' => '',
	'DOCUMENT_ROOT' => realpath($this_dir.'/./../www'))
);	// set application url

$_ERROR = new _Error;				// load error class
$site 	= new app;					// load application
$DB 	= new Database; 			// Database connection
$DB->Connect($database, true);		// set db connection
?>