<?php

/**
 * Copyright (c) 1998-2011, imegah.com, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 * * Neither the name of the imegah.com nor the
 * names of its contributors may be used to endorse or promote products
 * derived from this software without specific prior written permission.
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
 * Define _DATABASE
 */
//define('_DATABASE', true);


/**
 * This class work with databases and there methods (ex: insert, update, delete, select and more).
 * @subpackage Database
 */
class Database
{

	/**
	 * @var boolen check if this is debuging mode or not.
	 */
	protected $_DEBUG;
	protected $_log = false;

	/**
	 * @var object $this
	 */
	protected static $_instance = null;

	/**
	 * @var object current database connection
	 */
	protected $_conn;
	protected $_stmt;

	/**
	 * @var string table name
	 */
	protected $_table;

	/**
	 * @var string table primary key
	 */
	protected $_table_pk;

	/**
	 * @var string primary key defult generator type (ex: increment or uniqe)
	 */
	protected $_table_pk_generate = 'increment';

	/**
	 * @var array all the fields as key and there value as value, used for select/insert/updateing data.
	 */
	protected $_row = array ();

	/**
	 * @return void
	 */
	public function __construct ()
	{
		if (defined('DEBUG'))
			$this->_DEBUG = DEBUG;
	}

	/**
	 * @return void
	 */
	public function __destruct ()
	{
		$this->clear();
		$this->_conn = null;
		$this->_stmt = null;
	}

	/**
	 * $this->field
	 * @return string
	 * @param string $key
	 */
	public function __get ($key)
	{
		return isset($this->_row["{$key}"]) ? $this->_row["{$key}"] : NULL;
	}

	/**
	 * $this->field_name = 'value of the field';
	 * @return void
	 * @param string $key
	 * @param string $value
	 */
	public function __set ($key, $value)
	{
		@$this->_row["{$key}"] = $value;
	}

	/**
	 * check if the value is set or not with isset($this->message)
	 * @return bool
	 * @param key $key
	 */
	public function __isset ($key)
	{
		return isset($this->_row["{$key}"]);
	}

	/**
	 * dynamically unsetting single value
	 * @return void
	 * @param string $key
	 */
	public function __unset ($key)
	{
		if (isset($this->{$key}))
			unset($this->_row["$key"]);
	}

	/**
	 * $this->Field('value')->Name('Basit')->Age('43')->Location('Denver');
	 * echo $this->Name();
	 * Note: if method conflicts with the database method, then use _ before the field name (ex: $this->_delete('yes');)
	 *
	 * @return object $this good for chaining (if no value is set, then it will pass the filed value)
	 * @param string $method_name
	 * @param array $arguments
	 */
	public function __call ($method_name, $arguments)
	{
		$key = preg_replace('/^_/', '', strtolower($method_name));

		if (!isset($arguments[0]))
			return @$this->_row["{$key}"];

		@$this->_row["{$key}"] = $arguments[0];
		return $this; // return for chaining
	}

	/**
	 * @param object $array [optional]
	 * @return object
	 */
	public function values (Array $array = null)
	{
		if ($array !== null)
			return $this->_row = $array;
		else
			return $this->_row;
	}

	/**
	 * clears properties values for rows, single row and fields for getting prepare to make new call.
	 * @return void
	 */
	protected function clear ()
	{
		// reset values
		$this->_row = array ();
		$this->_stmt = false;
	}

	public function log ($log)
	{
		$this->_log = $log;
	}


	/**
	 * you should use this inside modules.
	 *
	 * @return object
	 * @param string $table    table name
	 * @param string $table_pk table primary key
	 * @param string $pk_generate[optional]
	 */
	public static function Set ($table, $table_pk, $pk_generate = false)
	{
		$thisInstance = self::getInstance();
		$thisInstance->_table = $table;
		$thisInstance->_table_pk = $table_pk;
		$thisInstance->_table_pk_generate = $pk_generate;

		return $thisInstance;
	}

	/**
	 * @return object
	 * @param object $database array('type', 'host', 'username', 'password', 'database')
	 * @param bool $persistent[optional]
	 */
	static public function getInstance (Array $database = null, $persistent = true)
	{
		if (!$database && self::$_instance)
			return self::$_instance;
		else
			$self = new self();

		if ($database)
			$self->Connect($database, $persistent);

		return $self;
	}

	/**
	 * @return void|false $this or false on error
	 * @param  array $database  array('type', 'host', 'username', 'password', 'database')
	 * @param  bool  $presistent
	 * @see Database::$_conn
	 * @uses Database::$_conn
	 */
	public function Connect (Array $database, $persistent = true)
	{
		if ($persistent and phpversion() >= '5.3.0')
			$database['1'] = 'p:' . $database['1'];

		$this->_conn = new mysqli($database['1'], $database['2'], $database['3'], $database['4']);

		if (mysqli_connect_error())
		{
			return $this->Error(null, 'Could not connect to the database '.mysqli_connect_errno());
			exit();
		}

		self::$_instance = $this;
	}

	public function connection ()
	{
		return $this->_conn;
	}

	public function stmt ()
	{
		return $this->_stmt;
	}

	public function table ()
	{
		return $this->_table;
	}

	public function tablePk ()
	{
		return $this->_table_pk;
	}

	/**
	 * close the connection for database.
	 * @return void
	 */
	public function close ($rowOnly=false)
	{
		if ($rowOnly)
		{
			$this->_stmt->close();
			$this->_stmt = null;
		}
		else
		{
			if ($this->_stmt)
				$this->_stmt->close();

			if ($this->_conn)
				$this->_conn->close();

			self::$_instance = null;
			$this->_conn = null;
			$this->_stmt = null;
		}
	}


	public function autocommit ($bool)
	{
		$this->_conn->autocommit($bool);
	}

	public function commit ()
	{
		$this->_conn->commit();
	}

	public function rollback ()
	{
		$this->_conn->rollback();
	}

	/**
	 * makes the exception for error handler, it is also good for catching errors with exception handler or with try {} catch (Exception $e) {}
	 *
	 * @return bool
	 * @param string $sql query string which cause the error
	 * @param string $message message of error which was accord while executing the query or runing the function.
	 */
	protected function Error ($sql, $message = NULL)
	{
		if ($sql && !$message)
		{
			if (!empty($this->_stmt->error))
				$message = $this->_stmt->error.' '.$this->_stmt->errno;
			elseif (!empty($this->_conn->error))
				$message = $this->_conn->error.' '.$this->_conn->errno;
		}


		if (!$this->_DEBUG)
			throw new Exception($message);
		else
		{
			if (defined('iERROR') && !_Error::is_firebug())
			{
				$build_array = array (
				'message' => $message, 'sql' => ($sql) ? $sql : null
				);

				throw new Exception(serialize($build_array));
			}
			else
				throw new Exception($message);
		}

		return false;
	}

	/**
	 * <code>
	 * if ($db->is_id(2322))
	 * return true;
	 * </code>
	 *
	 * @return string  works like boolen
	 * @param object $id
	 */
	public function is_id ($id)
	{
		if (!$this->_table || !$this->_table_pk)
			$this->Error(null, 'Table or primary key field is empty.');

		$id = $this->escape_string($id);
		$sql = "select {$this->_table_pk} from {$this->table} where {$this->_table_pk} = '$id'";

		return $this->getValue($sql);
	}

	/**
	 *
	 * @param object $value
	 * @param object $field
	 * @param object $table [optional] if not set, then it will try to get the table name from $this->_table
	 * @return string  if it exist, then returns it, else false
	 */
	public function is_value ($value, $field, $table = false)
	{
		if (!$this->_table && !$table)
			$this->Error(null, 'Table is not set.');

		if (!$field)
			$this->Error(null, 'field name is empty.');

		if (!$table)
			$table = $this->_table;

		$value = $this->escape_string($value);
		$sql = "select {$field} from {$table} where {$field} = '$value'";

		return ($this->getValue($sql) === false) ? false : true;
	}

	/**
	 * executing query while checking for the error, if error found then throw exception
	 *
	 * @return false|true false on error, resource on success if rows found.
	 * @param string $sql SQL statment to execute from the database	*
	 * @uses Database::Error()
	 * @uses Database::_conn
	 */
	public function query ($sql)
	{
		if (!$result = $this->_conn->query($sql))
			return $this->Error($sql);

		if ($this->_log)
		{
			if (defined('iERROR'))
				_Error::console($sql);
			else
				echo $sql;
		}

		return $result;
	}

	public function escape_string ($string)
	{
		return $this->_conn->real_escape_string($string);
	}

	public function fetch ($stmt = false)
	{
		if ($stmt)
			$row = $stmt->fetch_assoc();
		else
			$row = $this->_stmt->fetch_assoc();

		if (is_array($row))
		{
			$row = array_map('stripslashes', $row); // stripslahes
			return $row;
		}
		else
	  		return false;
	}

/**
	 * @return integer
	 * @param  string  $sql
	 * @param  string  $expr[optional] really rarely needed for join tables.
	 */
	public function count ($sql, $expr = '*')
	{
		$sql = $this->count_query($sql, $expr);
		$count = $this->getValue($sql);
		return $count ? $count : 0;
	}

	/**
	 * generates the count query. needed it for cache query.
	 *
	 * @return string
	 * @param  string  $sql[optional]
	 * @param  string  $expr[optional] really rarely needed for join tables. Added by Basit
	 */
	public function count_query ($sql, $expr = '*')
	{
		return preg_replace('#select(.*?)from#is', "select count($expr) as counted from ", $sql, 1);
	}

	public function showing_count ()
	{
		return $this->_stmt->num_rows;
	}

	public function affected_rows ()
	{
		return $this->_stmt->affected_rows;
	}

	/**
	 * This will load the db row with matching id of a primary key or a field (if you specify second argument).
	 *
	 * @return object
	 * @param string $id
	 * @param string $where_field
	 * @param string $select_fields
	 */
	public function load ($id_or_condition, $select_fields = '*')
	{
		$condition = preg_match('/[[:space:]]/', $id_or_condition);

		if (!$this->_table || (!$this->_table_pk && !$condition))
			$this->Error(null, 'Table or primary field is empty, Please provide table or primary field.');


		$pk = $this->_table_pk;

		$sql = "select $select_fields from {$this->_table} where ";
		$sql .= $condition ? $id_or_condition : "$pk = '".$this->escape_string($id_or_condition)."'";

		return $this->Single($sql);
	}

	/**
	 * Alias of Select, but second and third parameter is hard coded.
	 * <code>
	 * $row = $DB->Single($sql = 'select * from photo');
	 * foreach($row2 as $field => $value)
	 * {
	 * echo "$field = $value <br>";
	 * }
	 * </code>
	 * @return object
	 * @param string $sql
	 */
	public function Single ($sql)
	{
		$this->clear(); // cleaning previous saved info

		if (!$this->_stmt = $this->query($sql . " LIMIT 0,1"))
			return $this->Error($sql); // no rows found then return false.

		if (!$row = $this->fetch()) // no rows found
			return false;

		$this->_row = $row;
		return $this;
	}

	/**
	 * Execute the query and pull only the limited rows, for faster load page and
	 * saves lots of resources.  you can also use other methods which goes with them
	 *
	 * @return false|object false on error, object on success
	 * @param string $sql
	 * @param integer $show
	 * @param integer $start
	 *
	 * @uses Database::Error()
	 * @see Paging class
	 */
	public function Select ($sql, $show = -1, $start = 0)
	{
		$this->clear(); // cleaning previous saved info

		if (!$this->_stmt = $this->query($sql . $this->sqlLimitStr($show, $start)))
			return $this->Error($sql); // no rows found then return false.

		if (!$row = $this->fetch()) // no rows found
			return false;

		return new Rows($sql, $this->_stmt, $row);
	}

	/**
	 * Execute the query and pull only the limited rows but return all as array.
	 *
	 * @return false|array false on error, array on success
	 * @param string $sql
	 * @param integer $show
	 * @param integer $start
	 *
	 * @uses Database::Select()
	 * @see Paging class
	 */
	public function SelectArray ($sql, $show = -1, $start = 0)
	{
		if (!$stmt = $this->query($sql . $this->sqlLimitStr($show, $start)))
			return $this->Error($sql, $stmt->error.' '.$stmt->errno); // no rows found then return false.

		if (!$row = $this->fetch($stmt)) // no rows found
			return false;

		$rows = new Rows($sql, $stmt, $row);

		$rows_array = array ();

		foreach ($rows as $Next) {
			$rows_array[] = $rows->values();
		}

		$stmt->close(); // free memory
		unset($stmt);
		unset($rows);

		return $rows_array;
	}

	private function sqlLimitStr ($show, $start)
	{
		// make vars safe to call, if any were being put in dynamically
		$show = intval($show);
		$show = !$show ? 1 : $show;
		$start = intval($start);
		$start = !$start ? 0 : $start;

		$offsetStr = ($start >= 0) ? "$start," : '';

		if ($show < 0)
			$show = '18446744073709551615';

		return " LIMIT $offsetStr$show";
	}

	/**
	 * lets one use object with cached raw data.
	 */
	public function SetCaches ($data_array, $single_row = false)
	{
		$this->clear(); // cleaning previous saved info

		if ($single_row)
		{
			$this->_row = $data_array;
			return $this;
		}
		else
		{
			$rows = new Rows();
			$rows->setCaches($data_array);
			return $rows;
		}
	}

	/**
	 * if parameter is not set, then it will save, else it will update, but make sure you called set method in your consturct method of the plugin method.
	 * <code>
	 * $this->DB = $this->Set(...); // in construct method
	 *
	 * $this->DB->name = 'basit';
	 * $this->DB->Save(2323); // basit id number to update the row;
	 * </code>
	 *
	 * @return string $id
	 * @param string|integer $id_or_condition[optional] you can pass id of pk or pass condition to start after where
	 */
	public function Save ($id_or_condition = false)
	{
		if (!$this->_table)
			return $this->Error(null, "please set table name before trying to save the data.");
		elseif ($id_or_condition && !$this->_table_pk)
			return $this->Error(null, "please set table primary key field before trying to update the data.");
		elseif (!$id_or_condition and !empty($this->{$this->_table_pk}) and $this->_table_pk_generate !== FALSE)
			return $this->Error(null, "empty the primary key value to save new row or put value in save method to update the same row.");

		$fields = $values = $update_set = $condition = null;

		if (!$id_or_condition && $this->_table_pk && !$this->{$this->_table_pk} and $this->_table_pk_generate !== FALSE) // add id, if dont exist
			$this->{$this->_table_pk} = ($this->_table_pk_generate == 'uniqe') ? $this->getUniqeKey() : null;

		$array_fields = $this->values();

		if ($id_or_condition) // if its update only, then remove pk value
			unset($array_fields["{$this->_table_pk}"]); // without it has problem on update, if condition field is last

		end($array_fields);
		$last = key($array_fields);
		reset($array_fields);

		foreach ($array_fields as $field => $value) {

			if (is_array($value))
				return $this->Error(null, "{$field} value as array, please serialize it or fixe's the value. saving value cannot be array!");

			$value = str_replace('%PK%', $this->{$this->_table_pk}, $value); // add pk value


			if ($value === 'NOW()') // skip addslashes on NOW() function for mysql for datetime
				$value = 'NOW()';
			elseif ($value === null) // null can be used for auto increment, obviously is not supported by all database, so i recommand to stay away from it, unless the project is small
				$value = 'NULL';
			elseif (is_numeric($value))// and $value == 0) // 0 is not being set without quote, dunno why, but dont work for enum
				$value = "'" . $value . "'";
			elseif (!is_numeric($value)) // numeric will not have quotes and every other data will have.
				$value = "'" . $this->escape_string($value) . "'";

			if (!$id_or_condition) // if not updating
			{
				$fields .= $field;
				$values .= $value;
			}
			else {
				if ($this->_table_pk == $field)
					continue;
				$update_set .= $field . ' = ' . $value;
			}

			if ($last != $field) // dont put comma in last fields, cause sql will give error
			{
				$fields .= ', ';
				$values .= ', ';
				$update_set .= ', ';
			}
		}

		if (!$id_or_condition) // if not updating
			$sql = "insert into {$this->_table} ({$fields}) values ({$values})";
		else {
			$condition = preg_match('/[[:space:]]/', $id_or_condition); // check if its condition or just id


			$sql = "update {$this->_table} set $update_set where ";
			$sql .= $condition ? $id_or_condition : "{$this->_table_pk} = '{$id_or_condition}'";
		}

		if (!$this->_stmt = $this->query($sql))
			return $this->Error($sql);
		elseif (empty($this->{$this->_table_pk}) and $this->_table_pk_generate == 'increment')
			$this->{$this->_table_pk} = $this->_conn->insert_id; // get inserted id

		if (!empty($this->{$this->_table_pk}))
			return $this->{$this->_table_pk};

		elseif ($id_or_condition && !$condition)
			return $id_or_condition;
		else
			return true;
	}

	/**
	 * delete the row from table, to set table and primary key, you can do it from set method.
	 *
	 * @return bool
	 * @param string|integer $id_or_condition[optional] you can pass id of pk or pass condition to start after where
	 */
	public function delete ($id_or_condition = null)
	{
		if (!$this->_table || !$this->_table_pk)
			$this->Error(null, 'Table or primary field is empty, Please provide table or primary field.');

		$condition = preg_match('/[[:space:]]/', $id_or_condition);
		$pk = $this->_table_pk;
		$id = (!$id_or_condition && isset($this->{$pk})) ? $this->{$pk} : $id_or_condition;

		$sql = "delete from {$this->_table} where ";
		$sql .= $condition ? $id_or_condition : "$pk = '".$this->escape_string($id)."'";

		if (!$this->_stmt = $this->query($sql))
			return $this->Error($sql);

		return true;
	}

	/**
	 * returns back the uniqe id, with checking id in the database.
	 * @return string
	 * @param integer $length[optional]
	 * @param string $chars[optional]
	 */
	public function getUniqeKey ($length = 11, $chars = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789')
	{
		// loops indefinite times, if ids exist then make new random id else return the value
		while (1) {
			$id = substr(str_shuffle(str_repeat($chars, 2)), 0, $length);
			if (!$this->is_id($id)) // if dont exist, then return it.
				return $id;
		}
	}

	/**
	 * pulling just one value from the search, it auto limits the rows to 1 row
	 * [NOTE]: you must give single select field, else it will produce error
	 *
	 * @return string|false false if no row exists.
	 * @param string $sql
	 */
	public function getValue ($sql)
	{
		if (!$stmt = $this->query($sql . ' LIMIT 0,1'))
			return $this->Error($sql, $stmt->error.' '.$stmt->errno); // no rows found then return false.

		if (!$row = $this->fetch($stmt)) // no rows found
			return false;

		$stmt->close(); // free memory
		unset($stmt);

		if (count($row) > 1) // more then one fields
			return $this->Error($sql, 'sql returns more then one field.');

		return current($row);
	}

	/**
	 * build array using only two fields, one will be key and second will be its value
	 * great for drop down list and for quick check up list
	 *
	 * @return array|false array on exist rows, false on no rows or error
	 * @param string $sql field values will use as key
	 * @param integer $show
	 * @param integer $start
	 *
	 * @access public
	 */
	public function getArray ($sql, $show = -1, $start = 0)
	{
		if (!$stmt = $this->query($sql . $this->sqlLimitStr($show, $start)))
			return $this->Error($sql, $stmt->error.' '.$stmt->errno); // no rows found then return false.

		if (!$row = $this->fetch($stmt)) // no rows found
			return false;

		$count_fields = count($row);
		if ($count_fields > 2)
			return $this->Error($sql, 'sql returns more then two fields.');
		if ($count_fields < 2)
			return $this->Error($sql, 'sql returns less then two fields.');

		$rows = new Rows($sql, $stmt, $row);

		$array = array();

		foreach ($rows as $Next) {
			$row = $rows->values();
			$key = current($row); // first var as key and second var as value
			$val = next($row);
			$array["{$key}"] = $val;
		}

		$stmt->close(); // free memory
		unset($stmt);
		unset($rows);

		return $array;
	}
}

class Rows implements Iterator
{
	private $_sql  = NULL;
	private $_stmt = NULL;

	private $_row = array ();

	private $_row_cached = array ();
	private $_currentRow = 0;
	private $_EOF = TRUE;

	public function __construct($sql = null, $stmt = null, $row = null)
	{
		$this->_sql  = $sql;
		$this->_stmt = $stmt;
		$this->_row  = $row;
		$this->_EOF = is_array($this->_row) ? FALSE : TRUE;
	}

	public function setCaches ($row_cached)
	{
		$this->_row_cached = $row_cached;
		$this->_currentRow = 0;
		$this->_EOF = is_array($this->_row_cached) ? FALSE : TRUE;
		$this->_row = array();
		$this->_sql = NULL;
		$this->_stmt= NULL;
	}

	public function close ()
	{
		$this->_stmt->mysqli_free_result();
	}

	/**
	 * Retrieve or sets the values for the $this->_row var, this method is good for setting or retrieving bulk of fields
	 * @param object $array [optional]
	 * @return object
	 */
	public function values (Array $array = null)
	{
		if ($array !== null)
			return $this->_row = $array;
		else
			return $this->_row;
	}

	/**
	 * $this->field
	 * @return string
	 * @param string $key
	 */
	public function __get ($key)
	{
		return isset($this->_row["{$key}"]) ? $this->_row["{$key}"] : NULL;
	}

	/**
	 * $this->field_name = 'value of the field';
	 * @return void
	 * @param string $key
	 * @param string $value
	 */
	public function __set ($key, $value)
	{
		@$this->_row["{$key}"] = $value;
	}

	/**
	 * check if the value is set or not with isset($this->message)
	 * @return bool
	 * @param key $key
	 */
	public function __isset ($key)
	{
		return isset($this->_row["{$key}"]);
	}

	/**
	 * dynamically unsetting single value
	 * @return void
	 * @param string $key
	 */
	public function __unset ($key)
	{
		if (isset($this->{$key}))
			unset($this->_row["$key"]);
	}

	/**
	 * following 5 methods are for Iterator
	 * @return void
	 */
	public function rewind ()
	{
		if (!empty($this->_row_cached))
		{
			reset($this->_row_cached);
			$this->_row = current($this->_row_cached);
			$this->_currentRow = 0;
		}
		elseif ($this->_stmt)
		{
			$this->_stmt->data_seek(0);
			$this->_currentRow = 0;
			$this->fetch();
		}
	}

	public function current ()
	{
		if (!empty($this->_row_cached))
			return current($this->_row_cached);
		else
			return $this->_EOF ? false : true;

		return false;
	}

	public function key ()
	{
		return 0;
	}

	public function next ()
	{
		if (!empty($this->_row_cached))
		{
			$this->_row = next($this->_row_cached);
			$this->_currentRow++;
			return true;
		}
		elseif ($this->_stmt)
		{
			if ($this->_EOF)
				return false;

			$this->_currentRow++;
			return $this->fetch();
		}

		return false;
	}

	public function valid ()
	{
		return $this->current() !== false;
	}

	private function fetch ()
	{
		$this->_row = $this->_stmt->fetch_assoc();

		if (is_array($this->_row))
		{
			$this->_row = array_map('stripslashes', $this->_row); // stripslahes
			$this->_EOF = false; // row found
			return true;
		}
		else
		{
			$this->_EOF = true; // no more rows found
			return false;
		}
	}
}
?>