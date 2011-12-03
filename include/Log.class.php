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
 * @copyright Copyright: 1998-2009 imegah.com, Inc.
 * @author Basit <basit@imegah.com>
 * @access public
 * @package	IMF
 */
/**
* Define _LOG
*/
define('_LOG', true);
/**
* Logger class for logging message(s) in any format user wants, this method is very useful for logging any type of data
* @subpackage Logger
*/
class Log implements Iterator
{
	/**
	 * @var object $this
	 */
	protected static $_instance = null;

	/**
	 * error types are error number has key and there names as value (useful for tracking error or log)
	 * @var array
	 */
	protected $_error_type = array (
							1    => "Error",
							2    => "Warning",
							4    => "Parsing Error"
							);
	/**
	 * log type specify, how you want to log. key has method name and value has file_path/table_structure or empty
	 * @see __construct, log::save()
	 * @var array
	 */
	protected $_log_type;

	/**
	 * firePHP class or it can be set from global variable
	 * @var class
	 */
	public $firephp;

	/**
	 * all the log fields are here
	 * @var array
	 */
	protected $log_data;

	/**
	 * second argument is important and you can set how you want to log the messages from it.
	 * <code>
	 * $log = new log(array(
	 * 				  'file' => '/path/to/log.txt',
	 *				  'firephp',
	 * 				  'database' => array(
	 * 							    'table' => 'table_name',
	 * 								'key' => 'primary key [optional]',
	 * 								'fields' => array('message', 'ip', 'time') // [optional] if not set, it will log all the registered fields
	 * 								)
	 * 					)
	 * 				);
	 * </code>
	 * @return void
	 * @param array $log_type
	 * @param array $error_type[optional]
	 */
    public function __construct(Array $log_type, Array $error_type = NULL)
    {
		if (is_array($error_type))
			$this->_error_type = $error_type;

		$this->log_type($log_type);

		self::$_instance = $this;
    }

	/**
	 * @return object
	 * @param object $database array('type', 'host', 'username', 'password', 'database')
	 * @param bool $persistent[optional]
	 */
	public static function getInstance(Array $log_type = null, Array $error_type = NULL)
	{
		if (!$log_type && self::$_instance)
			return self::$_instance;
		else
			$self = new self($log_type, $error_type);

		return $self;
	}

	/**
	 * removes previous log messages from log_data array.
	 * @return void
	 */
	public function __destruct() {
		unset($this->log_data);
	}

	/**
	 * @see __construct
	 * @return void
	 * @param array $log_type
	 */
	protected function log_type (Array $log_type)
	{
		$this->_log_type = $log_type;
	}

	/**
	 * for printing or viewing the log as string.
	 * @return string
	 */
	public function __toString()
	{
		$log =  $this->parse(date('H:i:s d/m/y', time())).
				$this->parse($this->error_no, '[]').
				$this->parse($this->error_type, '[]').
				$this->parse($this->section, '[]').
				$this->parse($this->message, ':  ').
				$this->parse($this->file, 'in  ').
				$this->parse($this->line, 'at line  ').
				$this->parse($this->sql, 'sql was  ').
				$this->parse($this->sql, '-  ').
				$this->parse_array($this);

		return $log;
	}

	/**
	 * $this->field
	 * @return string
	 * @param string $key
	 */
	public function __get ($key)
	{
		return  (isset($this->log_data["{$key}"])) ? $this->log_data["{$key}"] : NULL;
	}

	/**
	 * $this->field_name = 'value of the field';
	 * @return void
	 * @param string $key
	 * @param string $value
	 */
	public function __set ($key, $value)
	{
		@$this->log_data["{$key}"] = $value;
	}

	/**
	 * check if the value is set or not with isset($this->message)
	 * @return bool
	 * @param key $key
	 */
	public function __isset($key)
	{
		return isset($this->log_data["{$key}"]);
	}

	/**
	 * dynamically unsetting single value
	 * @return void
	 * @param string $key
	 */
	public function __unset($key)
	{
		if (isset($this->log_data["$key"]))
			unset($this->log_data["$key"]);
	}

	/**
	 * following 6 methods (count method is extra) are for Iterator
	 * @return void
	 */
	public function rewind()
	{
		reset($this->log_data);
	}

	public function current ()
	{
		return current($this->log_data);
	}

	public function key()
	{
		return key($this->log_data);
	}

	public function next()
	{
		return next($this->log_data);
	}

	public function valid()
	{
		return $this->current() !== false;
	}
	public function count ()
	{
		return count($this->log_data);
	}

	/**
	 * This method should be used only and try to ignore using other methods directly, this method logs
	 * all the log types which user wants. log type can be set from log::__construct/log::log_type
	 *
	 *
	 * @see log::log_type, __contruct
	 * @return string|array if more then one log was set, then return will be array, else it will be string
	 * @param string|array $message
	 * @param integer $error_no[optional]
	 * @param string $file[optional]
	 * @param integer $line[optional]
	 * @param string $section[optional]
	 */
	public function log ($message, $error_no = NULL, $file = NULL, $line = NULL, $section = NULL)
	{
		unset($this); // unset previous log

		$this->log_message($message);
		if (!is_array($message))
		{
			if ($error_no) {
				$this->error_no = $error_no;
				if (isset($this->_error_type["{$error_no}"]))
					$this->error_type = $this->_error_type["{$error_no}"];
			}
			if ($section)
				$this->section = $section;
			if ($file)
				$this->file = $file;
			if ($line)
				$this->line = $line;
		}

		return $this->save(); // done
	}

	/**
	 * this method calls all the log type methods and returns the log
	 * @return string|array if more then one log was set, then return will be array, else it will be string
	 */
	public function save () {
		$return_log = array();

		if (!is_array($this->_log_type))
			return array();

		foreach ($this->_log_type as $method => $connection)
		{
			if ($method == 'firephp' || $connection == 'firephp')
				$return_log["{$method}"] = $this->firephp();
			else
				$return_log["{$method}"] = $this->{$method}($connection);
		}

		// if log is one, then send log only, else send array
		if (count($return_log) == 1)
			return $return_log["{$method}"];
		else
			return $return_log;
	}

	/**
	 * @return void
	 * @param array $array
	 */
	protected function log_message ($message)
	{
		unset($this); // unset previous log

		if (is_array($message))
		{
			foreach ($message as $key => $value)
			{
				//@$this->log_data = $message;
				$this->{$key} = $value;
			}
		}
		else
			$this->message = $message;
	}

	/**
	 * This method saves us from writing to many if condition, when wraping text around it, if the value
	 * is null, then it dosn't wrap value around it and dosn't use separator. this method is really helpful
	 * @return string
	 * @param object $value
	 * @param object $wrap[optional]
	 * @param object $separator[optional]
	 */
	protected function parse ($value, $wrap = NULL, $separator = " ")
	{
		if ($value)
		{
			if ($wrap)
			{
				$first_wrap = substr($wrap, 0, -1);
				$last_wrap = substr($wrap, -1);
				$value = $first_wrap.$value.$last_wrap;
			}

			$value = $separator.$value;
		}

		return $value;
	}

	/**
	 * @see log::parse
	 * @return string
	 * @param array $values
	 * @param string $wrap[optional] if 'key' word is passed, it will replace it with field name
	 * @param string $separator[optional]
	 * @param array $filter_array[optional] filter the fields from the log, its good, if you already included that in your log and you want to filter them, so they dont repeat.
	 */
	protected function parse_array ($values, $wrap = NULL, $separator = " ", Array $filter_array = array('time', 'error_no', 'error_type', 'section', 'line', 'file', 'message', 'sql'))
	{
		if (!is_array($values))
			return NULL;

		$log = null;
		foreach ($values as $key => $value) {
			if (is_array($filter_array) && in_array($key, $filter_array))
				continue;

			// search any word called key in wrap and replace it with the field name
			$wrap = str_replace('key', $key, $wrap);

			// append and continue logging.
			$log .= $this->parse($value, $wrap, $separator);
		}

		return $log;
	}

	/**
	 * logs the log in the file
	 * @return string
	 * @param string $file /path/to/log.txt
	 * @param string|array $message[optional]
	 */
	public function file ($file, $message = NULL)
	{
		// Let's make sure the file exists and is writable first.
		if ($file && !is_writable($file))
		{
			throw new Exception("file not writable/found '{$file}'");
			return false;
		}

		if ($message)
			$this->log_message($message);

		$log =  $this->parse(date('H:i:s d/m/y', time())).
				$this->parse($this->error_no, '[]').
				$this->parse($this->error_type, '[]').
				$this->parse($this->section, '[]').
				$this->parse($this->line).
				$this->parse($this->file, 'at  ').
				$this->parse($this->message).
				$this->parse($this->sql, '-  ').
				$this->parse_array($this).
				"\n";

		// open the file and write to the file
		if (!$file_pointer = fopen($file, 'a'))
			return false;
		if (!fwrite($file_pointer, $log))
			return false;
		fclose($file_pointer);

		return $log;
	}

	/**
	 * logs the log in the csv file
	 * @return string
	 * @param string $file /path/to/log.csv
	 * @param string|array $message[optional]
	 */
	public function csv ($file, $message = NULL)
	{
		// Let's make sure the file exists and is writable first.
		if ($file && !is_writable($file))
		{
			throw new Exception("file not writable/found '{$file}'");
			return false;
		}

		if ($message)
			$this->log_message($message);

		$clone_this = clone $this;
		$clone_this = array_map('addslashes', $clone_this);

		$log =  $this->parse(date('H:i:s d/m/y', time()), '""', "\t").
				$this->parse($clone_this->error_no, '""', "\t").
				$this->parse($clone_this->error_type, '""', "\t").
				$this->parse($clone_this->section, '""', "\t").
				$this->parse($clone_this->line, '""', "\t").
				$this->parse($clone_this->file, '""', "\t").
				$this->parse($clone_this->message, '""', "\t").
				$this->parse($clone_this->sql, '""',"\t").
				$this->parse_array($clone_this, '""', "\t").
				"\n";

		// open the file and write to the file
		if (!$file_pointer = fopen($file, 'a'))
			return false;
		if (!fwrite($file_pointer, $log))
			return false;
		fclose($file_pointer);

		return $log;
	}

	/**
	 * logs the log in the database,  you can limit the fields, if not all the fields are required
	 * @return string|false
	 * @param array $table_structure	array('table' => 'table_name',
	 * 										  'key'   => 'primary key name (value is null)[optional]',
	 * 										  'fields' => array('datetime', 'line', 'message') NOTE: if this provided, then auto building fields will disable
	 * 										   												which means, you have to pass all the fields, which you need to log.
	 * @param array|string $message[optional]
	 * @param object $DB_CONN[optional] database connection
	 */
	public function database (Array $table_structure, $message = NULL, Database &$DB_CONN = NULL)
	{
		global $DB;
		if (!$DB_CONN)
			$DB_CONN &= isset($DB) ? $DB : false;
		if (!$DB_conn)
			return false;

		if ($message)
			$this->log_message($message);

		$clone_this = clone $this;
		$clone_this = array_map('addslashes', $clone_this);

		$fields = isset($table_structure['key']) ? $table_structure['key'].', ' : NULL;
		$values = isset($table_structure['key']) ? 'NULL, ' : NULL;

		foreach ($clone_this as $key => $value)
		{
			if (isset($table_structure['fields']))
			{
				if (!in_array($key, $table_structure['fields']))
					continue;
			}

			$fields .= $this->parse($key, null, ', ');
			$values .= $this->parse($value, '""', ', ');
		}

		$sql = "insert into {$table_structure['table']} ({$fields}) values ({$values})";

		if (!$DB_CONN->Execute($insert))
			return false;

		return $values;
	}

	/**
	 * sends the log in the email
	 * @return string
	 * @param string $to_email
	 * @param string|array $message[optional]
	 */
	public function mail ($to_email, $message = NULL) {

		if ($message)
			$this->log_message($message);

		$subject = 	'Error Log: '.
					$this->parse($this->section).
		 			$this->parse($this->error_no, '[]').
					$this->parse($this->error_type, '[]');

		$log =  $this->parse($this->message);
				$this->parse($this->line, 'on line  ').
				$this->parse($this->file, 'at  ').
				$this->parse($this->sql, 'sql was  ').
				"\n\n\n".
				$this->parse_array($this, 'key:  ', "\n");

		if (@!mail($to_email, $subject, $log, "From: noreply@{$_SERVER['HTTP_HOST']}"))
			return false;

		return $log;
	}

	/**
	 * logs the log in the fireBug consol
	 * @return array
	 * @param string|array $message[optional]
	 */
	public function FirePHP($message = NULL)
	{
		global $firephp;

		if (!$this->firephp && isset($firephp))
		{
			$this->firephp = $firephp;
		} elseif (!$this->firephp && !isset($firephp))
		{
			$this->firephp = FirePHP::getInstance(true);
		}

		if (!$this->firephp)
			return false;

		if ($message)
			$this->log_message($message);

		if ($this->count() > 1)
		{
			$group = 	$this->parse($this->section).
			 			$this->parse((isset($this->_error_type["{$this->error_no}"])) ? $this->_error_type["{$this->error_no}"] : $this->error_no, '[]').
						$this->parse($this->message, ':  ');

			if (!$group)
				$group = date('d/m/Y H:i:s', time());

			$this->firephp->group($group);
			foreach ($this as $key => $value)
			{
				$this->firephp->log(is_array($value) ? $value : $key.': '.$value);
			}
			$this->firephp->groupEnd();
		} else
		{
			$this->firephp->log($this->message);
		}

		return $this;
	}

	/**
	 * returns the xml format log, which user can print/send/save manually
	 * @return string
	 * @param string|array $message[optional]
	 */
	public function xml ($message = NULL)
	{
		if ($message)
			$this->log_message($message);

		$log = "<log>\n";
		$log .= "\t<datetime>".datetime('d/m/Y H:i:s', time())."</datetime>\n";
		foreach($this as $key => $value)
		{
			if (is_array($value))
				$value = serialize($value);
			$log .= "\t<{$key}>{$value}</{$key}>\n";
		}
		$log .= "</log>\n\n";

		return $log;
	}

	/**
	 * returns the json format log, which user can print/send/save manually
	 * @return string
	 * @param string|array $message[optional]
	 */
	public function json ($message = NULL)
	{
		if ($message)
			$this->log_message($message);

		$message = $this->log_data;
		$message['datetime'] = date('d/m/Y H:i:s', time());

		return json_encode($message);
	}

}
?>