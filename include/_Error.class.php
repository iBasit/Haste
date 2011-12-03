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
* Define iERROR
*/
define('iERROR', true);
/**
* Error handler class for handling error more proper and show user friendly errors to the user, to tight
* up your security from not displaying error to user, instant log it.
* @subpackage Error_Handler
*/
class _Error extends Log
{

	/**
	* 0: false or 1: ture, for debuging the code much proper information display.
	* @var boolen
	*/
	private $DEBUG;

	private static $instance;

	/**
	 * path of the firephp class file
	 * @var string
	 */
	private $firephp_path;

	/**
	 * @var object
	 */
	private $FirePHPLevel;

	/**
	 * @var string
	 */
	private $error_level;

	/**
	 * error types are error number has key and there names as value (useful for tracking error or log)
	 * @var array
	 */
	protected $_error_type = array (
							E_ERROR              => 'Error',
			                E_WARNING            => 'Warning',
			                E_PARSE              => 'Parsing Error',
			                E_NOTICE             => 'Notice',
			                E_CORE_ERROR         => 'Core Error',
			                E_CORE_WARNING       => 'Core Warning',
			                E_COMPILE_ERROR      => 'Compile Error',
			                E_COMPILE_WARNING    => 'Compile Warning',
			                E_USER_ERROR         => 'User Error',
			                E_USER_WARNING       => 'User Warning',
			                E_USER_NOTICE        => 'User Notice',
			                E_STRICT             => 'Runtime Notice',
			                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
							);


	/**
	 * contructer sets the error handler method and exception handler method to the php engine.
	 * you can use error_reporting to control which methods should be log or not log.
	 *
	 * @return void
	 * @param array  $log_type
	 * @param string $firephp_path
	 * @uses DEBUG
	 */
	public function __construct (Array $log_type = array(), $firephp_path = NULL)
	{
		global $firephp;

		// set error handler and exception handler
		set_error_handler(array($this, 'log'));
		set_exception_handler(array($this, 'log_exception'));

		if (defined('DEBUG'))
			$this->DEBUG = DEBUG;

		if (!$this->firephp_path = (!empty($log_type['firePHP'])) ? $log_type['firePHP'] : $firephp_path)
			$this->firephp_path = realpath(dirname(__FILE__).'/./FirePHP.class.php');

		$this->log_type($log_type);

		self::$instance = $this;
	}

	/**
	 * for printing or viewing the log as string.
	 * @return string
	 */
	final function __toString()
	{

		$log = 	$this->parse($this->section).
		 		$this->parse((isset($this->_error_type["{$this->error_no}"])) ? $this->_error_type["{$this->error_no}"] : $this->error_no, '[]').
				$this->parse($this->message, ':  ').
				$this->parse($this->file, 'in  ').
				$this->parse($this->line, 'at line  ').
				$this->parse($this->sql, 'sql was  ').
				$this->parse_array($this, '[key:  ]');


		return $log;
	}

	/**
	 * @see __construct
	 * @return void
	 * @param string $key
	 * @param mixed  $value
	 */
	public function add_log_type ($key, $value)
	{
		$this->_log_type["{$key}"] = $value;
	}

	/**
	 * @see __construct
	 * @return void
	 * @param string $key
	 */
	public function remove_log_type ($key)
	{
		if (isset($this->_log_type["{$key}"]))
			unset($this->_log_type["{$key}"]);
	}

	/**
	 * logs the error according to log_type which was defined with the construct method.
	 * this also shows FireBugPHP error, if debug mode was turned on.
	 *
	 * @return void
	 * @param integer $error_number error number is passed by the system
	 * @param string  $message 	message of the error for debuging the error
	 * @param string  $file 		absolute path of the error page.
	 * @param integer $line 		line number of the page, debuging purpose
	 * @param array   $vars  	array is passed to check the whole envoirment of the running time
	 * @uses Log
	 */
	public function log ($error_no, $message, $file, $line, $vars) {
		// Don't throw exception if error reporting is switched off
	    if (error_reporting() == 0) {
	   		return;
	   	}

		// Only throw exceptions for errors we are asking for
	    if (error_reporting() & $error_no)
		{
			switch ($error_no)
			{
	            case E_PARSE:
	            case E_ERROR:
	            case E_CORE_ERROR:
	            case E_COMPILE_ERROR:
	            case E_USER_ERROR:
	                $this->error_level = 'Fatal Error';
	                //$level = LOG_ERROR;
					$this->FirePHPLevel = ($this->DEBUG) ? FirePHP::ERROR : false;
	            break;
	            case E_WARNING:
	            case E_USER_WARNING:
	            case E_COMPILE_WARNING:
	            case E_RECOVERABLE_ERROR:
	                $this->error_level = 'Warning';
	                //$level = LOG_WARNING;
					$this->FirePHPLevel = ($this->DEBUG) ? FirePHP::WARN : false;
	            break;
	            case E_NOTICE:
	            case E_USER_NOTICE:
	                $this->error_level = 'Notice';
	                //$level =ss LOG_NOTICE;
					$this->FirePHPLevel = ($this->DEBUG) ? FirePHP::INFO : false;
	            break;
	            default:
					$this->error_level = 'Notice';
	                $this->FirePHPLevel = ($this->DEBUG) ? FirePHP::INFO : false;
	            break;
	        }

			// emptying all dunno why -- unset($this->log_data); // unset previous log
	        $message_array = @unserialize($message);
			if ($message_array)
				$message = $message_array;

			$this->log_message($message);
			$this->file = $file;
			$this->line = $line;
			$this->error_no = $error_no;
			$this->ip = $this->ip();

			//echo "$message in $file on $line <br>";
			// have to use firePHP_log, cause if try to throw exception here, then everything will stop working or stop working until its catched.
			$this->firephp_log();
			$this->save(); // display/save log

			if ($this->error_level == 'Fatal Error')
			{
          		die();
      		}
		}

	}

	/**
	 * logs the exception and also display it in FireBugPHP if debug mode was set.
	 * @return void
	 * @param object $e
	 */
	public function log_exception($e)
	{
		if ($this->DEBUG)
			$this->firephp_log($e);

		if ($message_array = @unserialize($e->getMessage()))
				$message = $message_array;
		else
			$message = $e->getMessage();

		$this->log_message($message);
		$this->file = $e->getFile();
		$this->line = $e->getLine();
		$this->error_no = $e->getCode();
		$this->ip = $this->ip();

		$this->save(); // display/save log
	}

	/**
	 * get the user ip
	 * @return string
	 */
	public function ip ()
	{
		// Get the real ip address
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (isset($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		else
			$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

		return $ip;
	}

	/**
	* logs all the error in the FireBugPHP consol.
	*
	* @return object $this
	* @param  object|array|string you can pass exception object in this, to log exception or just pass array or string of message.
	*/
	public function firephp_log ($message = null)
	{
		if (!$this->DEBUG) // dont log, if debug mode is not TRUE (for security purpose.)
			return;

		$FirePHP = FirePHP::getInstance(true);


		if (defined('_APP') && !App::$domain_only) // this can be true only, if error was triggered from SSH console
		{
			print $this->ssh_log($message);
			return $this;
		}
		elseif (headers_sent() || !$FirePHP->detectClientExtension())
		{
			print $this->html_log($message);
			return $this;
		}

		if ($message instanceof Exception || $message instanceof ErrorException)
		{
			//print_r($message);
			//$message = unserialize($str)
			$FirePHP->fb($message);
		} else
		{
			if ($message)
				$this->log_message($message);

			$log = @sprintf('%s', $this); // convert object to string

			$FirePHP->fb($log, $this->FirePHPLevel ? $this->FirePHPLevel : FirePHP::INFO);

			// trace giving recursive error on any error like undefined error
			//$FirePHP->trace('trace...');
		}

		return $this;
	}

	/**
	 * This method is helpful for debugging the application and saves time for rewriting huge line again and again.
	 * writes the logs in the firephp console
	 * @var $log
	 */
	public static function console ($log)
	{
		FirePHP::getInstance(true)->log($log);

		if (!self::$instance)
			$_error = new self();
		return self::$instance;
	}

	public static function is_firebug ()
	{
		if (headers_sent() || !FirePHP::getInstance(true)->detectClientExtension())
			return false;
		else
			return true;
	}

	/**
	 * disable firephp logs, cause firephp_exception is taking care of that, if debug mode is on (for security).
	 * @return void
	 */
	final function firephp () {}

	/**
	 * build html to display to the user.
	 *
	 * @staticvar integer $count check if css needs to be included if function did not call before
	 * @return string builded html to display
	 * @see _Error::Handler()
	 */
	function html_log ($message = null) {
		static $count;

		if ($message)
			$this->log_message($message);

		$log = sprintf('%s', $this);
		$trace_array = debug_backtrace();
		$build_errors='';
		$trace='';
		foreach($trace_array as $trace)
		{
			if (!isset($trace['file']))
				$trace['file'] = '';

			if (!isset($trace['line']))
				$trace['line'] = '';

			if (!isset($trace['class']))
				$trace['class'] = '';

			if (!isset($trace['type']))
				$trace['type'] = '';

			if (!isset($trace['args']))
				$trace['args'] = '';

			if($trace['function'])
			{
				if ($trace['class'] == '_error')
					continue;

				$trigger_error 	= (strtolower($trace['function']) == 'trigger_error') ? 'class="trigger_error"' : '';
				$message 		= (!$trace['class']) ? '' : '<font class="class">'.$trace['class'].$trace['type'].'</font>';
				$message 	   .= '<font class="function">'.$trace['function'].'</font> ';
				$message       .= '<font class="bracket">(</font> ';

				if(is_array($trace['args']))
				{
					$message .= '<font class="argument">';
					$args='';
					foreach($trace['args'] as $argument)
					{
						$message .= '<font class="quote">\'</font>';
						$message .= is_string($argument) ? $argument : gettype($argument);
						$message .= '<font class="argument_separater">,</font> ';
						$message .= '<font class="quote">\'</font>';
					}
					$message .= '</font>';
				}
				$message .= ' <font class="bracket">)</font>';
			}

			$file_name = isset($trace['file']) ? substr($trace['file'], -20) : '-';
			$build_errors .=<<< trace
			 	<tr {$trigger_error}>
				  <td nowrap>{$message}</td>
				  <td>{$trace["line"]}</td>
				  <td title="{$trace["file"]}">...{$file_name}</td>
				</tr>
trace;
		}

		$trace_output_css =<<< output_css
			<style type="text/css">
			<!--
				.trace table {	font-family: Verdana, Arial, Helvetica, sans-serif; border: 1px dashed #999999;	letter-spacing: normal;	text-align: justify; word-spacing: normal; padding: 1px; margin: 2px;	text-indent: 2pt;	width: 400px; }
				.trace th {	font-family: "Courier New", Courier, mono;	font-size: 14px; font-weight: bold;	color: #F33;	background-color: #FFFFCC;	font-style: normal;	line-height: normal;	font-variant: normal;	text-decoration: none;}
				.trace td {	padding-top: 4px; font-size: 12px;	border-top-width: 1px;	border-top-style: dotted;	border-top-color: #000000;	text-align: left; }
				.trace .function {	font-family: "Courier New", Courier, mono;	font-size: 12px; color: #339933; text-transform: capitalize; }
				.trace .argument {	color: #003366;	font-family: Geneva, Arial, Helvetica, sans-serif;	font-size: 10px;}
				.trace .argument_separater { letter-spacing: 5px;	color: #FF0000; }
				.trace .quote {	color: #996600;}
				.trace .class {	color: #CC3300;	font-family: Georgia, "Times New Roman", Times, serif;	font-size: 10px; font-weight: bold;}
				.trace .trigger_error {	background-color: #F2F2F2;	border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #000000;}
				.trace .bracket {	font-size: 10px;	font-weight: bolder;	color: #000000;	font-family: Verdana, Arial, Helvetica, sans-serif;}
				.trace {	padding: 5px;}
			-->
			</style>
output_css;
		$trace_output =<<< output
			<div class="trace">

				<table width="50%" border="1" cellspacing="1" cellpadding="1">
				  <caption>
				    Please report this error to the site Administrator to resolve this issue.
				  </caption>
				  <tr>
				    <th colspan="3" align="left" scope="col">{$log}</th>
				  </tr>
				  <tr>
				    <td align="center" bgcolor="#F7F7F7">Trace Info</td>
				    <td align="center" bgcolor="#F7F7F7">Line</td>
				    <td align="center" bgcolor="#F7F7F7">File</td>
				  </tr>
				  {$build_errors}
				</table>
			</div>
output;

		if (!$count)
			$trace_output = $trace_output.$trace_output_css;
		++$count;
		return $trace_output;
	}

	function ssh_log ($message)
	{
		if ($message)
			$this->log_message($message);

		return $this."\n";
	}
}
?>