<?php



namespace GeorgPreissl\Projects\Model;

use Contao\CoreBundle\File\ModelMetadataTrait;
use Contao\Model\Collection;
use Contao\Model;
use Contao\Date;

class ProjectsModel extends Model
{
	use ModelMetadataTrait;

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_projects';

	/**
	 * Find a published project item from one or more project archives by its ID or alias
	 *
	 * @param mixed $varId      The numeric ID or alias name
	 * @param array $arrPids    An array of parent IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return ProjectsModel|null The model or null if there are no projects
	 */
	public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");
		$arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")";

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, $varId, $arrOptions);
	}

	/**
	 * Find published project items by their parent ID
	 *
	 * @param array   $arrPids     An array of project archive IDs
	 * @param boolean $blnFeatured If true, return only featured projects, if false, return only unfeatured projects
	 * @param integer $intLimit    An optional limit
	 * @param integer $intOffset   An optional offset
	 * @param array   $arrOptions  An optional options array
	 *
	 * @return Collection|ProjectsModel[]|ProjectsModel|null A collection of models or null if there are no projects
	 */
	public static function findPublishedByPids($arrPids, $blnFeatured=null, $intLimit=0, $intOffset=0, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured='1'";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = "$t.date DESC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		return static::findBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Count published project items by their parent ID
	 *
	 * @param array   $arrPids     An array of project archive IDs
	 * @param boolean $blnFeatured If true, return only featured projects, if false, return only unfeatured projects
	 * @param array   $arrOptions  An optional options array
	 *
	 * @return integer The number of project items
	 */
	public static function countPublishedByPids($arrPids, $blnFeatured=null, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return 0;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured='1'";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::countBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find published project items with the default redirect target by their parent ID
	 *
	 * @param integer $intPid     The project archive ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ProjectsModel[]|ProjectsModel|null A collection of models or null if there are no projects
	 */
	public static function findPublishedDefaultByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.source='default'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.date DESC";
		}

		return static::findBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find published project items by their parent ID
	 *
	 * @param integer $intId      The project archive ID
	 * @param integer $intLimit   An optional limit
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ProjectsModel[]|ProjectsModel|null A collection of models or null if there are no projects
	 */
	public static function findPublishedByPid($intId, $intLimit=0, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.date DESC";
		}

		if ($intLimit > 0)
		{
			$arrOptions['limit'] = $intLimit;
		}

		return static::findBy($arrColumns, $intId, $arrOptions);
	}

	/**
	 * Find all published project items of a certain period of time by their parent ID
	 *
	 * @param integer $intFrom    The start date as Unix timestamp
	 * @param integer $intTo      The end date as Unix timestamp
	 * @param array   $arrPids    An array of project archive IDs
	 * @param integer $intLimit   An optional limit
	 * @param integer $intOffset  An optional offset
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ProjectsModel[]|ProjectsModel|null A collection of models or null if there are no projects
	 */
	public static function findPublishedFromToByPids($intFrom, $intTo, $arrPids, $intLimit=0, $intOffset=0, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = "$t.date DESC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		return static::findBy($arrColumns, array($intFrom, $intTo), $arrOptions);
	}

	/**
	 * Count all published project items of a certain period of time by their parent ID
	 *
	 * @param integer $intFrom    The start date as Unix timestamp
	 * @param integer $intTo      The end date as Unix timestamp
	 * @param array   $arrPids    An array of project archive IDs
	 * @param array   $arrOptions An optional options array
	 *
	 * @return integer The number of project items
	 */
	public static function countPublishedFromToByPids($intFrom, $intTo, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::countBy($arrColumns, array($intFrom, $intTo), $arrOptions);
	}
}

class_alias(ProjectsModel::class, 'ProjectsModel');
