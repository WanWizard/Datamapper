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

use Datamapper\Model as Model;

/**
 * Datamapper platform interface base class
 */
abstract class Base
{
	/**
	 * available core extension classes
	 */
	protected $core_methods = array(
		'Datamapper\\Core\\Query' => array(
			'dynamic' => array(
				'get',
			),
			'static' => array(
			),
		),
	);

	/**
	 * loaded extension classes
	 */
	protected $extension_methods = array(
	);

	/**
	 * load the global Datamapper configuration and merge it with the model configuration
	 *
	 * @param	array	model specific configuration
	 *
	 * @return	array	parsed and validated configuration
	 */
	abstract function get_config(array $config);

	/**
	 * return a database connection
	 *
	 * @param	string	database to connect to
	 *
	 * @return \Cabinet\DBAL\Connection
	 */
	abstract function get_connection($database);

	/**
	 * return the defined extensions that could be loaded
	 *
	 * @param	bool	$overload	if true we allow overloading of core methods
	 *
	 * @return	array	array with loaded extension methods
	 */
	abstract function get_extensions($overload = false);

	/**
	 * return the cached properties for this model (if exist)
	 *
	 * @return	array|null	the cached properties
	 */
	abstract function get_properties($table_name);

	/**
	 * cache this models properties
	 *
	 * @param	array	model properties
	 *
	 * @return 	void
	 */
	abstract function set_properties(array $properties);

	/**
	 * Pluralize a string
	 *
	 * @param	string	string to pluralize
	 *
	 * @return	string	pluralized result
	 */
	abstract function pluralize($string);


}
