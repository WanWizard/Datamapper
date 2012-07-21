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

namespace Datamapper\Platform\Fuelphp;

use Datamapper\Model as Model;
use Datamapper\Platform\Base as Base;

/**
 * Datamapper Codeigniter interface class
 */
class Driverv1 extends Base
{
	/**
	 * @var	array	storage for created connections
	 */
	static $connnections = array();

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
		// load the Datamapper config file and get the config values
		if ( $defaults = \Config::load('datamapper', true) )
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
			// load the database configuration file
			\Config::load('db', true);

			// and get the config
			$db = \Config::get('db');

			// does it contain what it should contain?
			if ( empty($db) )
			{
				throw new \Datamapper\Exceptions\DatamapperException('No database connection settings were found in the database config file.');
			}

			// if no database was defined, get CI's configured default
			if ( empty($database) )
			{
				if ( ! isset($db['active']) )
				{
					throw new \Datamapper\Exceptions\DatamapperException('You have not specified an active database in the config file.');
				}
				$database = $db['active'];
			}

			// do we have a database definition for the defined database
			if ( empty($db[$database]) )
			{
				throw new \Datamapper\Exceptions\DatamapperException('You have specified an invalid active database in the config file.');
			}

			// store the database definition
			$db[$database]['name'] = $database;
			$database = $db[$database];
		}

		// do we already have a connection to this database
		if ( ! isset(static::$connnections[$database['name']]) )
		{
			static::$connnections[$database['name']] = \Cabinet\DBAL\Db::connection(array(
					'driver' => $database['type'],
					'dsn' => empty($database['connection']['dsn']) ? null : $database['connection']['dsn'],
					'username' => $database['connection']['username'],
					'password' => $database['connection']['password'],
					'host' => empty($database['connection']['host']) ? null : $database['connection']['host'],
					'port' => empty($database['connection']['port']) ? null : $database['connection']['port'],
					'database' => empty($database['connection']['database']) ? null : $database['connection']['database'],
				));

			// define the profiler callbacks for this connection
			static::$connnections[$database['name']]->profilerCallbacks(
				function($data) { \Profiler::start('Datamapper', $data['query']); },
				function($data) { \Profiler::stop(''); }
			);
		}

		// return the database connection
		return static::$connnections[$database['name']];
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
		// return the pluralized version
		return \Inflector::tableize($string);
	}
}
