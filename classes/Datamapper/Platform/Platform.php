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

namespace Datamapper\Platform;

/**
 * Datamapper platform interface base class
 */
class Platform
{
	/**
	 * @var  \Datamapper\Platform\Base  platform driver to use
	 */
	protected static $_platform = null;

	/**
	 * Fetch the database connection name to use
	 *
	 * @return  null|string
	 */
	public static function initialize()
	{
		// make sure this is only executed once
		static $initialized = false;

		if ( ! $initialized )
		{
			// running in a CodeIgniter 2.0.0+ environment?
			if ( defined('CI_VERSION') and version_compare(CI_VERSION, '2.0') >= 0 )
			{
				static::$_platform = new Codeigniter\Driver();
			}

			// running in a FuelPHP 1.x environment?
			elseif ( class_exists('Fuel\\Core\\Fuel', false) )
			{
				die('need to implement the Fuel 1.x driver here');
				static::$_platform = new Fuelphp\Driverv1();
			}

			// running in a FuelPHP 2.x environment?
			elseif ( class_exists('\Fuel\Kernel\Environment', false) )
			{
				die('need to implement the Fuel 2.x driver here');
				static::$_platform = new Fuelphp\Driverv2();
			}

			// we don't know this platform, bail out!
			else
			{
				throw new Exceptions\DatamapperException('This platform is currently not supported by Datamapper');
			}

			// and we're done!
			$initialized = true;
		}
	}

	/**
	 * load the global Datamapper configuration and merge it with the model configuration
	 *
	 * @param	array	model specific configuration
	 *
	 * @return	array	parsed and validated configuration
	 */
	public static function get_config(array $config)
	{
		return static::$_platform->get_config($config);
	}

	/**
	 * load the global Datamapper configuration and merge it with the model configuration
	 *
	 * @param	mixed	database definition to connect to
	 *
	 * @return	\Cabinet\DBAL\Connection
	 */
	public static function get_connection($database)
	{
		return static::$_platform->get_connection($database);
	}

	/**
	 * return the cached properties for this model (if exist)
	 *
	 * @return	array|null	the cached properties
	 */
	public static function get_properties($table_name)
	{
		return static::$_platform->get_properties($table_name);
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
		return static::$_platform->set_properties($properties);
	}

	/**
	 * Pluralize a string
	 *
	 * @param	string	string to pluralize
	 *
	 * @return	string	pluralized result
	 */
	public static function pluralize($string)
	{
		return static::$_platform->pluralize($string);
	}

	/**
	 * Returns the namespace of the given class name.
	 *
	 * @param	string	$class_name	the class name
	 *
	 * @return	string	the string without the namespace
	 */
	public static function get_namespace($class_name)
	{
		$class_name = trim($class_name, '\\');
		if ($last_separator = strrpos($class_name, '\\'))
		{
			return substr($class_name, 0, $last_separator + 1);
		}
		return '';
	}

	/**
	 * Takes the namespace off the given class name.
	 *
	 * @param	string	$class_name	the class name
	 * @return	string	the string without the namespace
	 */
	public static function denamespace($class_name)
	{
		$class_name = trim($class_name, '\\');
		if ($last_separator = strrpos($class_name, '\\'))
		{
			$class_name = substr($class_name, $last_separator + 1);
		}
		return $class_name;
	}
}
