<?php
/**
 * Datamapper ORM - Vendor package loader library
 *
 * @package     DataMapper ORM
 * @category    Core
 * @author      Harro "WanWizard" Verton
 * @author		Fuel Development Team
 * @license		MIT License
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

namespace Datamapper\Core;

/**
 * Datamapper Query core extension class
 */
class Query
{
	/**
	 * get() implementation
	 */
	public static function get($object, $limit = null, $offset = null)
	{
		// set limit and offset if needed
		is_null($limit) or $object->query()->limit($limit);
		is_null($offset) or $object->query()->offset($offset);

		// run the query
		$result = $object->query()->execute();

		// reset the query object
		$object->query(true);

		// hydrate the results
		return $object->hydrate($result);
	}
}
