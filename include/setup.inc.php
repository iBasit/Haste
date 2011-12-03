<?php
define('DEBUG', TRUE);		// debug mode

error_reporting(E_ALL);
$this_dir = dirname(__FILE__);
require_once(realpath($this_dir.'/./include/App.class.php'));        // site app include
require_once(realpath($this_dir.'/./include/Log.class.php'));  	     // error include
require_once(realpath($this_dir.'/./include/_Error.class.php')); 	 // error include
require_once(realpath($this_dir.'/./include/Database.class.php'));   // db include
require_once(realpath($this_dir.'/./include/resources.class.php'));  // resources

$database = array(
	"MySql",		// Type of Database.
	"localhost",	// Database Host.
	"root",			// Database Username.
	"",				// Database Password.
	"haste"			// Database Name.
	);

App::set_url(array('APP_PATH' => '', 'DOCUMENT_ROOT' => realpath($this_dir.'/./../www')));	// set application url
App::$auto_include_dir = FALSE;

$_SERVER['REQUEST_METHOD'] = empty($_SERVER['REQUEST_METHOD']) ? 'GET' : $_SERVER['REQUEST_METHOD']; // shell support
$_SERVER['REQUEST_METHOD'] = isset($_REQUEST['method']) ? strtoupper($_REQUEST['method']) : $_SERVER['REQUEST_METHOD'];


$_ERROR = new _Error;				// load error class
$site 	= new app;					// load application
$DB 	= new Database; 			// Database connection
$DB->Connect($database, true);		// set db connection
$site->init();						// load site settings
?>