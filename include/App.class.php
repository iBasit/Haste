<?php
/**
 * Copyright (c) 1998-2011, imegah.com, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the imegah.com nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY imegah.com ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL imegah.com BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @link http://imegah.com/
 * @version 2.0
 * @copyright Copyright: 1998-2011 imegah.com, Inc.
 * @author Basit <basit@imegah.com>
 * @access public
 * @package	IMF
 */

/**
 * Define _APP
 */
define('_APP', true);

/**
* This class help you setup the application on the page.
* @subpackage Application
*/
class App
{
	/**
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * @var string name of the application
	 */
	public static $name;

	/**
	 * @var string version of the application
	 */
	public static $version;

	/**
	 * @var string language code
	 */
	public static $language = 'us';
	/**
	 * @var string root path to the application
	 */
	public static $root;

	/**
	 * @var string sub-folder of the application, if there is any.
	 */
	public static $folder;

	/**
	 * @var string url path
	 */
	public static $path;

	/**
	 * @var string with sub-domain name if exist
	 */
	public static $domain;

	/**
	 * @var string domain only, without sub-domain name, if exist
	 */
	public static $domain_only;

	/**
	 * @var string domain url http://www.site-domain.com:2342  (with port, if its not 8080/80).
	 */
	public static $domain_url;

	/**
	 * @var string application url http://www.site-domain.com:2342/app_root/
	 */
	public static $url;

	/**
	 * @var string complete path with request uri http://site-domain.com/folder/sub-folder/index.php (but not the ?vars=232..., check app::$self_url for that)
	 */
	public static $request_url;

	/**
	 * @var string its self explanatory
	 */
	public static $self_url;

	/**
	 * @var array of include dir path for classes, which will be automatically added to the application on call.
	 */
	public static $auto_include_dir = array();

	/**
	 * @var array keeps the list of classes that added automatically with the complete path
	 */
	public static $auto_included = array();

	/**
	 * @var bool enable or disable the auto include method error handling, good for third party libraries
	 */
	public static $auto_include_error = true;

	/**
	 * @var array list of actions which was set from url (ex: /tags/search/actoin3/action4...)
	 */
	public static $action = array();

	public static $format = null;

	public static $format_find = array('.atom', '.json', '.xml', '.jpeg', '.jpg', '.gif', '.png');


	/**
	 * starts configuring with few default stuff.
	 * @see self::_init()
	 * @return void
	 */
	public function __construct()
	{
		$this->_init();
		self::$_instance = $this;
	}

	/**
	 * gets the instance of this class or build new one
	 * @return object instance of this class
	 */
	public static function getInstance ()
	{
		if (!self::$_instance)
			$instance = new self();

		return self::$_instance;
	}

	/**
	 * closes all the connections (db, session)
	 * @return void
	 */
	public function __destruct()
	{
		global $DB;
		if (session_id()) // if session started, then destroy it.
			@session_destroy();

		if (is_object($DB))
			@$DB->close();
	}

	/**
	 * configuration of the application. sets the table, include path, class auto loader, ini settings, fixes the magic quote problem.
	 * @return void
	 */
	protected function _init ()
	{
		global $TABLES, $TABLE_PREFIX, $INI, $INCLUDE;

		$this->_fix_magic_quotes();
		$this->_PUT_DELETE_Marge_with_REQUEST();

		if (isset($TABLES))
			$this->set_tables($TABLES, isset($TABLE_PREFIX) ? $TABLE_PREFIX : null);

		if (isset($INI))
			$this->set_ini($INI);

		if (isset($INCLUDE) && is_array($INCLUDE))
			self::$auto_include_dir = $INCLUDE;

		spl_autoload_register(array($this, 'load')); // class auto loader

	}

	/**
	 * includes the class with already define include dirs.
	 * @see self::$auto_include_dir
	 * @return bool
	 * @param string $class
	 */
	public  function load($class)
	{
		if (class_exists($class, false))
        	return;

		$auto_include_dir = self::$auto_include_dir;
		$root = self::$root;
		$path = $class.'.class.php';

		foreach ($auto_include_dir as $include_dir)
		{
			$path = $root.$include_dir.$class.'.class.php';

			if(file_exists($path))
			{
	        	self::$auto_included[] = "$class - $path";
				require_once($path);
	        	return true;
			}

		}

		// throw trigger, exception is not allowed and _Error wont work.
		if (self::$auto_include_error)
			trigger_error("The requested library {$class}, could not be found at {$path}", E_USER_ERROR);
	}

	/**
	 * this method calls when setting the application, but can be use to override if needed to, but should be avoided to use override.
	 *
	 * @return void
	 * @param array $server_info  you can set $_SERVER (its auto pass defaultly, if no parameter has passed yet), which will have all the required values, but if you want
	 * 							  to set your own values for some anonymouse reason like for (api use), then you
	 * 							  can just override with few key values (SERVER_PROTOCOL, SERVER_PORT, HTTP_HOST, REQUEST_URI, SCRIPT_FILENAME, DOCUMENT_ROOT)
	 * 	  						  please read php doc on $_SERVER with there key values, to know what values you should replace and how.
	 */
	public static function set_url(Array $server_info = null)
	{
		$default_server_info = array(
			'SERVER_PROTOCOL' 	=> isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '',
			'SERVER_PORT' 		=> isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '',
			'HTTP_HOST' 		=> isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
			'REQUEST_URI' 		=> isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			'DOCUMENT_ROOT' 	=> isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '',
			'SCRIPT_FILENAME' 	=> isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '',
			'APP_PATH'			=> 'include/_lib/'
			);

		$server_info = array_merge($default_server_info, is_array($server_info) ? $server_info : array());

		// it will help from undifiend error, if run in shell command.
		$HOST 	  = isset($server_info['HTTP_HOST']) ? $server_info['HTTP_HOST'] : 'localhost';
		$PROTOCOL = $server_info['SERVER_PROTOCOL'];
		$PORT 	  = $server_info['SERVER_PORT'];
		$URI 	  = $server_info['REQUEST_URI'];

		$row_protocol = $PROTOCOL;
		$port = $PORT;
		if ($port == 80 || $port == 8080)
			$port = NULL;
		else
			$port = ':' . $port;

		$protocol = strtolower(substr($row_protocol,0, strpos($row_protocol,'/')));

		if (!empty($server_info['APP_PATH']))
		{
			$current_dir = str_replace('\\', '/', dirname(__FILE__));
			$root_dir = str_replace('\\', '/', $server_info['DOCUMENT_ROOT']);

			// fixed for the dynamic dir changes on hosts like dreamhost
			$root_dir = explode('/', $root_dir);
			$root_dir = end($root_dir);

			$new_dir = @explode($root_dir, $current_dir, 2);

			if (is_array($new_dir))
				$new_dir = end($new_dir);
			else
				$new_dir = $current_dir;

			$server_path = preg_replace("/^\/|\/$/", '', $server_info['APP_PATH']);
			$app_path = str_replace($server_path, '', $new_dir);
			$app_path = preg_replace("/^\/|\/$/", '', $app_path);

			if ($app_path)
			{
				$app_path = '/'.$app_path;
				if (!preg_match('/\/$/', $app_path))
					$app_path .= '/';
			}
		}
		else
			$app_path = '';

		// set the settings for the application/site
		self::$domain_url	= sprintf("%s://%s%s", $protocol, $HOST, $port);
		self::$url 			= self::$domain_url.$app_path; // error
		self::$request_url  = self::$domain_url.preg_replace('/(\?)+.*/', '', $URI);
		self::$self_url 	= sprintf("%s://%s%s%s", $protocol,	$HOST, $port, $URI);
		self::$root			= $server_info['DOCUMENT_ROOT'].'/'.$app_path; //error
		self::$folder 		= (preg_match("/^/",$app_path)) ? $app_path : '/'.$app_path; //error
		self::$path			= preg_replace('/(\?)+.*/', '', $URI);
		self::$domain		= self::host($HOST);
		self::$domain_only  = self::host($HOST, false);
	}

	/**
	 * this is useful for setting actions in request_uri and it will put all the values in actions
	 * and return it
	 *
	 * @return array
	 * @param  string $actions  pass action seprated by /
	 */
	public static function set_actions($actions)
	{
		$count = 0;
		$temp_actions = str_ireplace(self::$format_find, '', $actions, $count);

		if ($count) // check if found any format
		{
			foreach (self::$format_find as $format)
			{
				//$format = str_ireplace('.','/', $format);
				if (preg_match('/\\'.$format.'/', $actions)) // assign the found format
				{
					self::$format = str_ireplace('.', '', $format);
					break; // dont loop more, cause json and jsonp is same for jsonp
				}
			}
		}

		$actions = $temp_actions; // now set the value
		$array = explode('/', $actions);
		if(is_array($array))
		{
			if (empty($array['0'])) // remove first empty key from array
				array_shift($array);

			$last = end($array);
			$key = key($array);
			if (empty($last)) // remove last empty key from array
			{
				unset($array["{$key}"]);
				$last = end($array);
				$key = key($array);
			}

			if (isset($array["{$key}"]))
			{
				$array["{$key}"] = $array["{$key}"];
			}

			self::$action = $array;
		} else
			self::$action['0'] = $actions;

		$array = array(0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 5 => ''); // undefine protection
		self::$action = array_merge(self::$action, $array);

		return self::$action;
	}

	/**
	 * checks if method is GET for rest api
	 * @return bool
	 */
	public static function GET()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'GET');
	}

	/**
	 * checks if method is POST for rest api
	 * @return bool
	 */
	final public static function POST()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * checks if method is POST for rest api.
	 * yes self::POST and self::CREATE is same method, just sometimes you need to use post for
	 * doing post on the site, which is not creating a row and programmer might get confuse, so
	 * thats why created two methods, that work same, but just less confusion error usage.
	 * @return bool
	 */
	final public static function CREATE()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * checks if method is PUT for rest api
	 * @return bool
	 */
	final public static function UPDATE()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'PUT');
	}

	/**
	 * checks if method is DELETE for rest api
	 * @return bool
	 */
	final public static function DELETE()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'DELETE');
	}

	/**
	 * sets smarty library and returns it back as object.
	 * @return object
	 * @param object $settings send array as object with following keys and there values.
	 * 							$SMARTY = (object) array(
	 * 							'template_dir'		=> '/themes/',			// template dir
	 * 							'compile_dir' 		=> '/themes/compile/', 	// compile dir
	 * 							'config_dir' 		=> '/themes/', 			// config dir
	 * 							'cache_dir' 		=> '/themes/cache/',  	// cacheing dir
	 * 							'cache'		 		=> true, 				// enable/disable caching
	 * 							'cache_lifetime' 	=> 0,					// caching lifetime
	 * 							'left_delimiter' 	=> '{',					// start delimeter
	 * 							'right_delimiter' 	=> '}'					// end delimeter
	 * 							);
	 */
	public static function set_smarty ($settings)
	{

		$settings->template_dir = $settings->template_dir ? self::$root.$settings->config_dir : null;
		$settings->config_dir 	= $settings->config_dir   ?	self::$root.$settings->config_dir : null;
		$settings->compile_dir 	= $settings->compile_dir  ?	self::$root.$settings->compile_dir : null;
		$settings->cache_dir 	= $settings->cache_dir  ?	self::$root.$settings->cache_dir : null;

		if (!is_dir($settings->template_dir))
			trigger_error('Smarty template directory dose not exists or path is not assign.', 256);
		if (!is_dir($settings->config_dir))
			trigger_error('Smarty config directory dose not exists or path is not assign.', 256);

		$smarty = new Smarty();

		$smarty->template_dir 	= $settings->template_dir;
		$smarty->compile_dir 	= $settings->compile_dir;
		$smarty->config_dir 	= $settings->config_dir;
		$smarty->cache_dir 		= $settings->cache_dir;
		$smarty->cache_lifetime = $settings->cache_lifetime;

		if ($settings->cache)
		{
			if (!is_dir($settings->compile_dir))
				trigger_error('Smarty compile directory dose not exists or path is not assign.', 256);
			if (!is_dir($settings->cache_dir))
				trigger_error('Smarty cache directory dose not exists or path is not assign.', 256);
			$smarty->caching = true;
		} else
			$smarty->caching = false;

		$smarty->left_delimiter = $settings->left_delimiter;
		$smarty->right_delimiter = $settings->right_delimiter;

		return $smarty;
	}

	/**
	 * starts the session.
	 * @return void
	 * @param integer|string $session_id[optional]
	 * @param object $DB[optional]
	 */
	public static function session_start ($session_id = false, Database &$DB = null)
	{
		// include db session libarary, if db connection is passed
		if (is_object($DB))
		{
			$session_path = self::$root."/include/adodb/session/adodb-session2.php";

			if (!defined('ADODB_SESSION') && $session_path)
				include($session_path);

			if (defined('ADODB_SESSION'))
			{
				$GLOBALS['ADODB_SESS_CONN'] = $DB->_conn;
				ADODB_Session::table(sessions);
			}
		}

		session_set_cookie_params(false, '/', self::$domain_only);
		session_name('sid');				// session new name
		if ($session_id) // set session id
			session_id($session_id);

		session_start();
	}

	/**
	 * check to see if the owner of session is same for given ip and session id
	 *
	 * @param string $session_id
	 * @param string $ip
	 * @return bool
	 */
	public static function is_session_owner ($session_id, $ip)
	{
		$session_id = addslashes($session_id);
		$ip = $ip ? addslashes($ip) : self::ip();
		$DB = Database::Set('sessions', 'sesskey');
		return $DB->get_value('sesskey', "where sesskey = '{$session_id}' and ip = '{$ip}'");
	}

	/**
	 * define the table names and also add prefix next to the name if assign the second argument.
	 * @return void
	 * @param array $tables key is name which will be defined name and its value will be the value that holds it.
	 * @param string $table_prefix[optional]  table prefix to assign to all tables
	 */
	public static function set_tables (Array $tables, $table_prefix = NULL)
	{
		if (!is_array($tables))
			return;

		foreach ($tables as $orignal_name => $new_name)
		{
			if (is_integer($orignal_name) && !defined($new_name)) // if no key is define, then make value has define name also
				define($new_name, $table_prefix.$new_name, 0);
			elseif (!defined($orignal_name))  // if key is define, then use that and value  its value.
				define($orignal_name, $table_prefix.$new_name, 0);
		}
	}

	/**
	 * configure the ini setting for the php.
	 * @return void
	 * @param array $INI ini name as key and followed by the value
	 */
	public static function set_ini (Array $INI)
	{
		if (!is_array($INI))
			return;

		foreach ($INI as $key => $value)
			ini_alter($key, $value);
	}

	/**
	 * create key and token value to use on the secure form (images)..
	 *
	 * @return array
	 * @access public
	 */
	public static function set_secure_token ()
	{
		// token id
		$number = rand(100000, 999999);
		$token = strtoupper(substr(md5($number),0,24));
		$key = strtoupper(substr(md5($token.$number),0,8));
		$_SESSION["{$token}"] = $key;

		return array('token' => $token, 'secure_key' => $key);
	}

	/**
	 * merges default values with new values if exist. this method is useful for reducing some work on
	 * assigning dynamic array of values
	 *
	 * @return void
	 * @param array $fields
	 * @param array $values  this is if its set then assign this
	 * @param array $else_values this is elseif it is set, then assign this else jst do null
	 */
	public static function array_flip_merge (Array $fields, Array &$values, Array &$else_values = NULL)
	{
		if (!is_array($fields))
			trigger_error('please set the pass array to set_smarty_assign_cond', 256);

		$else_values = $else_values ? $else_values : array();

		$fields = array_flip($fields);
		$fields = array_map('app::set_empty', $fields);
		return array_merge($fields, $else_values, $values);
	}

	/**
	 * this method is used in app::array_flip_merge() and its not used for anything else.
	 * @return null
	 * @param string $val
	 */
	private static function set_empty ($val)
	{
		return "";
	}

	/**
	 * build the mail class to send. <fon color=red><b>phpMailer.inc.php is required by the method</b></fon>
	 *
	 * sets the mail class to send the email, following is the example
	 * <code>
	 *	......
	 * 	if (!$application->set_mail(array("to_email..., from...)));
	 *	  echo 'error';
	 *  else
	 *    echo 'success';
	 *
	 *
	 *  // required if return_class sended int he array
	 *  if (!$mail->Send()) print 'error';
	 *  else print 'success sending the mail';
	 * </code>
	 *
	 * @return resource|bool
	 * @param array $array		 array of key and value, some are required, but not all of them are required
	 * 							 array(	"to_email" 	 => "user@gmail.com",  			// required
	 * 					  				"to_name"  	 => "Eric James",      			// optional
	 * 									"from_email" => "admin@gmail.com" 			// required, you can set this from application var
 	 * 									"from_name"  => "Company Name"   			// required, you can set this from application var
	 * 					  				"subject"  	 => "You have Registered to us",// required
	 * 					 				"body"	   	 => "message here.. ", 			// required
	 * 									"text"  	 => false or 'this is text...', // optional, default is false, on true it will use body as text msg or you can put text msg in this then it will use body as html and this is text, using both togahter
	 * 									"smtp"       => 'smtp.gmail.com',  			// optional, defaul it gets from ini settings, you can set this from application var
	 * 									"wordwrap"   => 100,						// optional, default is 100, you can set this from application var
	 * 									"return_class"=> true						// optional, defaul is false, if true then it wont run send method and returns the class for more modifications..
	 * 							 );
	 *
	 *
	 */
	public static function set_mail (Array $array)
	{
		global $INI;

		// extract all the key values into variables
		if (is_array($array))
			extract($array, EXTR_OVERWRITE);

		$from_email = isset($from_email) ? $from_email : '';
		$from_name 	= isset($from_name) ? $from_name : '';
		$wordwrap 	= isset($wordwrap) ? $wordwrap : 100;
		$smtp 		= isset($smtp) ? $smtp : !empty($INI['SMTP']) ? $INI['SMTP'] : '';
		$text 		= isset($text) ? $text : false;

		// SMTP setting
		if (!$to_email || !$subject || !$body || !$from_email || !$from_name)
			throw new Exception('App::set_mail - Please set the required values');

		$mail = new phpmailer();
		$mail->IsMail();

		if ($smtp)
			$mail->Host = $smtp; // SMTP server

		if ($wordwrap)
			$mail->WordWrap = $wordwrap;

		$mail->From = $from_email;
		$mail->FromName = $from_name;
		$mail->Subject = $subject;

		// set to email
		if (empty($to_name))
			$mail->AddAddress($to_email);
		else
			$mail->AddAddress($to_email, $to_name);

		// set the body
		if ($text === true)
			$mail->Body = $body;
		else {
			$mail->IsHTML(true);
			$mail->Body = $body;
		}
		if (strlen($text) > 4)
			$mail->AltBody = $text;

		if (!empty($return_class))
			return $mail;
		else
			return $mail->send();
	}

	/**
	 * This method is helpful for checking of referer site for api or banned use most importantly.
	 *
	 * @return string   sub.domain-name.com or domain-name.com
	 * @param string $domain_url  http://sub.domain-name.com/page...
	 * @param bool   $with_subdomain[optional]
	 */
	public static function host($domain_url, $with_subdomain = true)
	{
		$domain_url = strtolower($domain_url);

		// get host name from URL
		if (!preg_match('@^(?:http://)?([^/]+)@i', $domain_url, $matches))
			return null;

		$host = str_replace('www.', '', $matches[1]);

		if ($with_subdomain)
			return $host;

		// get last two segments of host name
		if (!preg_match('/[^.]+\.[^.]+$/', $host, $matches)) // localhost support
			return $host;
		else // for normal domains..
			return $matches['0'];
	}

	/**
	 * return the proper ip address of the client, if he is behind proxy server
	 * then use {@link App::Proxy()} to check the proxy of the isp server.
	 *
	 * @return string ip address of the client
	 * @access public
	 * @static
	 * @see App::Proxy()
	 */
	public static function ip ()
	{
		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
		{
			if (!empty($_SERVER["HTTP_CLIENT_IP"]))
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			else if (!empty($_SERVER["REMOTE_ADDR"]))
				$ip = $_SERVER["REMOTE_ADDR"];
			else // shell support
				$ip = '';
		}

		return $ip;
	}

	/**
	 * proxy address of the client, if client is seting behind proxy server
	 *
	 * @return string proxy address of the client
	 * @access public
	 * @static
	 * @see App::IP()
	 */
	public static function proxy ()
	{
		if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) // if its not set, then return null
			return null;

		if (!empty($_SERVER["HTTP_CLIENT_IP"]))
			$proxy = $_SERVER["HTTP_CLIENT_IP"];
		else
			$proxy = $_SERVER["REMOTE_ADDR"];
		return $proxy;
	}

	public static function date_duration ($old_date, $now_date = false)
	{
		// set duration
		$timestamp = strtotime($old_date);
		$duration =  self::date_duration_array($timestamp);
		if (isset($duration['month']) or isset($duration['months'])) // display date and not duration after month
			$date = date('F j, Y', $timestamp);
		else
		{
			if (isset($duration['weeks']))
				$duration_show = 'weeks';
			elseif (isset($duration['week']))
				$duration_show = 'week';
			elseif (isset($duration['days']))
				$duration_show = 'days';
			elseif (isset($duration['day']))
				$duration_show = 'day';
			elseif (isset($duration['hours']))
				$duration_show = 'hours';
			elseif (isset($duration['hour']))
				$duration_show = 'hour';
			elseif (isset($duration['minutes']))
				$duration_show = 'minutes';
			elseif (isset($duration['minute']))
				$duration_show = 'minute';
			elseif (isset($duration['seconds']))
				$duration_show = 'seconds';
			else
				$duration_show = 'second';

			$language = Language::getInstance();
			$date = sprintf($language->{$duration_show}, ($duration_show == 'second') ? '' : $duration["{$duration_show}"]);
		}

		return $date;
	}

	/**
	 * Get the duration of between two dates
	 * @param int $old_date time()
	 * @param int $now_date [optional] default time()
	 * @return array duration in array starting with year/years if its in year/month/week/days/hour/minute/second/(s)
	 */
	public static function date_duration_array ($old_date, $now_date = false)
	{
		$now_date = $now_date ? $now_date : time();

		$blocks = array(
		    array('name' => 'year', 'seconds'	=>    60*60*24*365   ),
		    array('name' => 'month','seconds'	=>    60*60*24*31   ),
		    array('name' => 'week',	'seconds' 	=>    60*60*24*7   ),
		    array('name' => 'day', 	'seconds' 	=>    60*60*24    ),
		    array('name' => 'hour', 'seconds' 	=>    60*60      ),
		    array('name' => 'minute','seconds' 	=>    60        ),
		    array('name' => 'second','seconds' 	=>    1        )
		    );

		$diff = abs($old_date-$now_date);

		$result = array();

		foreach($blocks as $block)
		{
		    if ($diff/$block['seconds'] >= 1)
		    {
		        $amount = floor($diff/$block['seconds']);
		        $plural = ($amount > 1) ? 's' : '';

		        $result["{$block['name']}{$plural}"] = $amount;
		        $diff -= $amount*$block['seconds'];
		    }
		}

		return $result;
	}

	/**
	 * Return human readable sizes
	 *
	 * @param int $size        Size
	 * @param int $unit        The maximum unit
	 * @param int $retstring   The return string format
	 * @param int $si          Whether to use SI prefixes
	 *
	 * @return string
	 * @access public
	 */
	public static function get_readable_filesize ($size, $unit = null, $retstring = false, $si = true)
	{
	    // Units
	    if ($si === true) {
	        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	        $mod   = 1000;
	    } else {
	        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
	        $mod   = 1024;
	    }
	    $ii = count($sizes) - 1;

	    // Max unit
	    $unit = array_search((string) $unit, $sizes);
	    if ($unit === null || $unit === false) {
	        $unit = $ii;
	    }

	    // Return string
	    if (!$retstring) {
	        $retstring = '%01.2f %s';
	    }

	    // Loop
	    $i = 0;
	    while ($unit != $i && $size >= 1024 && $i < $ii) {
	        $size /= $mod;
	        $i++;
	    }

	    return sprintf($retstring, $size, $sizes[$i]);
	}

	/**
	 * retrive PUT and DELETE methods var and marge it with _REQUEST
	 * @return void
	 */
	private function _PUT_DELETE_Marge_with_REQUEST ()
	{
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'))
		{
		    $array = array();
			parse_str(file_get_contents('php://input'), $array);
			$_REQUEST = array_merge($array, $_REQUEST);
		}
	}

	/**
	 * if magic quotes are on, it will make it safer to run with on or off
	 * this will help the programmer to write code more freely, without worring
	 * about the magic quotes gpc
	 *
	 * @return void
	 * @param string $var
	 * @param bool $sybase
	 */
	private function _fix_magic_quotes ($var = NULL, $sybase = NULL)
	{
		// if sybase style quoting isn't specified, use ini setting
		if (!isset ($sybase))
			$sybase = ini_get ('magic_quotes_sybase');

		// if no var is specified, fix all affected superglobals
		if (!isset ($var))
		{
			// if magic quotes is enabled
			if (get_magic_quotes_gpc ())
			{
				// workaround because magic_quotes does not change $_SERVER['argv']
				$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : NULL;

				// fix all affected arrays
				foreach ( array ('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $var )
				{
					$GLOBALS[$var] = $this->_fix_magic_quotes ($GLOBALS[$var], $sybase);
				}

				$_SERVER['argv'] = $argv;

				// turn off magic quotes, this is so scripts which
				// are sensitive to the setting will work correctly
				ini_set ('magic_quotes_gpc', 0);
			}

			// disable magic_quotes_sybase
			if ( $sybase )
			{
				ini_set ('magic_quotes_sybase', 0);
			}

			// disable magic_quotes_runtime
			ini_set ('magic_quotes_runtime', 0);
			return TRUE;
		}

		// if var is an array, fix each element
		if ( is_array ($var) )
		{
			foreach ( $var as $key => $val )
			{
				$var[$key] = $this->_fix_magic_quotes ($val, $sybase);
			}

			return $var;
		}

		// if var is a string, strip slashes
		if ( is_string ($var) )
		{
			return $sybase ? str_replace ('\'\'', '\'', $var) : stripslashes ($var);
		}

		// otherwise ignore
		return $var;
	}
}
?>