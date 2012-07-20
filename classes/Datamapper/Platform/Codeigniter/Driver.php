<?php
/**
 * Datamapper ORM - Vendor package loader library
 *
 * @package     DataMapper ORM
 * @category    Platforms
 * @author      Harro "WanWizard" Verton
 * @author		Fuel Development Team
 * @license		MIT License
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

namespace Datamapper\Platform\Codeigniter;

use Datamapper\Model as Model;
use Datamapper\Platform\Base as Base;

// create a fake CI's DB class, we need that for the profiler interface
class_exists('CI_DB') or eval('class CI_DB {}');

/**
 * Datamapper Codeigniter interface class
 */
class Driver extends Base
{
	/**
	 * @var	object	pointer to the CodeIgniter global object
	 */
	protected static $_CI = null;

	/**
	 * @var	string	name of the database definition to use, or null for the default
	 */
	protected static $_database = null;

	/**
	 * load the global Datamapper configuration and merge it with the model configuration
	 *
	 * @param	array	model specific configuration
	 * @param	string	name of the model class
	 *
	 * @return	array	parsed and validated configuration
	 */
	public function get_config(array $config)
	{
		// get the CI global instance
		empty(static::$_CI) and static::$_CI =& get_instance();

		// load the global Datamapper config file
		static::$_CI->config->load('datamapper', true, true);

		// get the config values
		if ( $defaults = static::$_CI->config->item('datamapper') )
		{
			$config = array_merge($defaults, $config);
		}

		// make sure they have the correct values : model_prefix
		empty($config['model_prefix']) and $config['model_prefix'] = '';

		// make sure they have the correct values : table_prefix
		empty($config['table_prefix']) and $config['table_prefix'] = '';

		// make sure they have the correct values : database
		if ( empty($config['database']) or ! is_string($config['database']) )
		{
			$config['database'] = null;
		}

		// return the config for this model
		return $config;
	}

	/**
	 * return a database connection
	 *
	 * @param	string	database to connect to
	 *
	 * @return \Cabinet\DBAL\Connection
	 */
	public function get_connection($database)
	{
		if ( ! is_array($database) )
		{
			// locate the database configuration file
			if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php'))
			{
				if ( ! file_exists($file_path = APPPATH.'config/database.php'))
				{
					throw new \Datamapper\Exceptions\DatamapperException('The configuration file database.php does not exist.');
				}
			}

			// and load it
			include($file_path);

			// does it contain what it should contain?
			if ( ! isset($db) OR count($db) == 0)
			{
				throw new \Datamapper\Exceptions\DatamapperException('No database connection settings were found in the database config file.');
			}

			// if no database was defined, get CI's configured default
			if ( empty($database) )
			{
				if ( ! isset($active_group) )
				{
					throw new \Datamapper\Exceptions\DatamapperException('You have not specified a default database connection group.');
				}
				$database = $active_group;
			}

			// do we have a database definition for the defined database
			if ( empty($db[$database]) )
			{
				throw new \Datamapper\Exceptions\DatamapperException('You have specified an invalid database connection group.');
			}

			// store the database definition
			$database = $db[$database];
		}

		// do we already have a connection for this database?
		if ( ! isset(static::$_CI->{'datamapper_'.$database['database']}) )
		{
			// return the database connection
			$conn = \Cabinet\DBAL\Db::connection(array(
				'host' => $database['hostname'],
				'port' => empty($database['port']) ? null : $database['port'],
				'driver' => $database['dbdriver'],
				'username' => $database['username'],
				'password' => $database['password'],
				'database' => $database['database'],
			));

			// create a fake CI DB class to facilitate query profiling
			static::$_CI->{'datamapper_'.$database['database']} = new Profiler($conn, $database['database']);
		}
		else
		{
			// re-use the existing connection for this database
			$conn = static::$_CI->{'datamapper_'.$database['database']}->connection;
		}

		// return the database connection
		return $conn;
	}

	/**
	 * return the cached properties for this model (if exist)
	 *
	 * @return	array|null	the cached properties
	 */
	public function get_properties($table_name)
	{
		// @TODO: for now we don't cache
		return null;
	}

	/**
	 * cache this models properties
	 *
	 * @param	array	model properties
	 *
	 * @return 	void
	 */
	public function set_properties(array $properties)
	{
		// @TODO: for now we don't cache
		return null;
	}

	/**
	 * Pluralize a string
	 *
	 * @param	string	string to pluralize
	 *
	 * @return	string	pluralized result
	 */
	public function pluralize($string)
	{

		// load the required CI inflector helper
		function_exists('plural') or static::$_CI->load->helper('inflector');

		// return the pluralized version
		return plural($string);
	}
}
