<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace GeorgPreissl\Projects;


class ProjectsModel extends \Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_projects';


	/**
	 * Find published project items by their parent ID and ID or alias
	 *
	 * @param mixed $varId      The numeric ID or alias name
	 * @param array $arrPids    An array of parent IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return static The ProjectModel or null if there are no project
	 */
	public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("($t.id=? OR $t.alias=?) AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		return static::findBy($arrColumns, array((is_numeric($varId) ? $varId : 0), $varId), $arrOptions);
	}


	/**
	 * Find published project items by their parent ID
	 * Diese Funktion wird von der Category-Funktion weiter unten in diesem File überschrieben!!!
	 * 
	 * @param array   $arrPids     An array of project archive IDs
	 * @param boolean $blnFeatured If true, return only featured project, if false, return only unfeatured project
	 * @param integer $intLimit    An optional limit
	 * @param integer $intOffset   An optional offset
	 * @param array   $arrOptions  An optional options array
	 *
	 * @return \Model\Collection|\ProjectModel[]|\ProjectModel|null A collection of models or null if there are no project
	 */
	/*
	public static function findPublishedByPids($arrPids, $blnFeatured=null, $intLimit=0, $intOffset=0, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured='1'";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		// Never return unpublished elements in the back end, so they don't end up in the RSS feed
		if (!BE_USER_LOGGED_IN || TL_MODE == 'BE')
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		if (!isset($arrOptions['order']))
		{
			// $arrOptions['order']  = "$t.date DESC";
			$arrOptions['order']  = "$t.sorting ASC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		return static::findBy($arrColumns, null, $arrOptions);
	}
	*/

	/**
	 * Count published project items by their parent ID
	 * Diese Funktion wird von der Category-Funktion weiter unten in diesem File überschrieben!!!
	 * 
	 * @param array   $arrPids     An array of project archive IDs
	 * @param boolean $blnFeatured If true, return only featured project, if false, return only unfeatured project
	 * @param array   $arrOptions  An optional options array
	 *
	 * @return integer The number of project items
	 */
	/*
	public static function countPublishedByPids($arrPids, $blnFeatured=null, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids))
		{
			return 0;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured='1'";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		return static::countBy($arrColumns, null, $arrOptions);
	}
	*/

	/**
	 * Find published project items with the default redirect target by their parent ID
	 *
	 * @param integer $intPid     The project archive ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return \Model\Collection|\ProjectModel[]|\ProjectModel|null A collection of models or null if there are no project
	 */
	public static function findPublishedDefaultByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.source='default'");

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
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
	 * @return \Model\Collection|\ProjectModel[]|\ProjectModel|null A collection of models or null if there are no project
	 */
	public static function findPublishedByPid($intId, $intLimit=0, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
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
	 * Diese Funktion wird von der Category-Funktion weiter unten in diesem File überschrieben!!!
	 * 
	 * @param integer $intFrom    The start date as Unix timestamp
	 * @param integer $intTo      The end date as Unix timestamp
	 * @param array   $arrPids    An array of project archive IDs
	 * @param integer $intLimit   An optional limit
	 * @param integer $intOffset  An optional offset
	 * @param array   $arrOptions An optional options array
	 *
	 * @return \Model\Collection|\ProjectModel[]|\ProjectModel|null A collection of models or null if there are no project
	 */
	/*
	public static function findPublishedFromToByPids($intFrom, $intTo, $arrPids, $intLimit=0, $intOffset=0, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = "$t.date DESC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		return static::findBy($arrColumns, array($intFrom, $intTo), $arrOptions);
	}
	*/


	/**
	 * Count all published project items of a certain period of time by their parent ID
	 * Diese Funktion wird von der Category-Funktion weiter unten in diesem File überschrieben!!!
	 * 
	 * @param integer $intFrom    The start date as Unix timestamp
	 * @param integer $intTo      The end date as Unix timestamp
	 * @param array   $arrPids    An array of project archive IDs
	 * @param array   $arrOptions An optional options array
	 *
	 * @return integer The number of project items
	 */
	/*
	public static function countPublishedFromToByPids($intFrom, $intTo, $arrPids, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

		if (!BE_USER_LOGGED_IN)
		{
			$time = \Date::floorToMinute();
			$arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
		}

		return static::countBy($arrColumns, array($intFrom, $intTo), $arrOptions);
	}
	*/






















    /**
     * Get the categories cache and return it as array
     * @return array
     */
    public static function getCategoriesCache()
    {
        static $arrCache;

        if (!is_array($arrCache)) {
            $arrCache = array();
            $objCategories = \Database::getInstance()->execute("SELECT * FROM tl_projects_categories");
            $arrCategories = array();

            while ($objCategories->next()) {
                // Include the parent IDs of each category
                if (!isset($arrCategories[$objCategories->category_id])) {
                    $arrCategories[$objCategories->category_id] = \Database::getInstance()->getParentRecords($objCategories->category_id, 'tl_projects_category');
                }

                foreach ($arrCategories[$objCategories->category_id] as $intParentCategory) {
                    $arrCache[$intParentCategory][] = $objCategories->project_id;
                }
            }
        }

        return $arrCache;
    }

	
    /**
     * Filter the project by categories
     * @param array
     * @return array
     */
    protected static function filterByCategories($arrColumns)
    {
        // wird bei Seitenaufruf immer 2x ausgefürht!??

        // $arrColumns:
        // Array
        // (
        //     [0] => tl_project.pid IN(1,3,2,4)
        //     [1] => (tl_project.start='' OR tl_project.start<1468769200) AND (tl_project.stop='' OR tl_project.stop>1468769200) AND tl_project.published=1
        // )        

        $t = static::$strTable;
        // string(10) "tl_project"


        // Use the default filter
        if (is_array($GLOBALS['PROJECT_FILTER_DEFAULT']) && !empty($GLOBALS['PROJECT_FILTER_DEFAULT'])) {
            $arrCategories = static::getCategoriesCache();

            if (!empty($arrCategories)) {
                $arrIds = array();

                // Get the project IDs for particular categories
                foreach ($GLOBALS['PROJECT_FILTER_DEFAULT'] as $category) {
                    if (isset($arrCategories[$category])) {
                        $arrIds = array_merge($arrCategories[$category], $arrIds);
                    }
                }

                $strKey = 'category';

                // Preserve the default category
                if ($GLOBALS['PROJECT_FILTER_PRESERVE']) {
                    $strKey = 'category_default';
                }

                $arrColumns[$strKey] = "$t.id IN (" . implode(',', (empty($arrIds) ? array(0) : array_unique($arrIds))) . ")";
            }
        }

        // Exclude particular project items
        if (is_array($GLOBALS['PROJECT_FILTER_EXCLUDE']) && !empty($GLOBALS['PROJECT_FILTER_EXCLUDE'])) {
            $arrColumns[] = "$t.id NOT IN (" . implode(',', array_map('intval', $GLOBALS['PROJECT_FILTER_EXCLUDE'])) . ")";
        }

        $strParam = ProjectsCategories::getParameterName();

        // Try to find by category
        if ($GLOBALS['PROJECT_FILTER_CATEGORIES'] && \Input::get($strParam)) {
            // $strClass = \ProjectsCategories\ProjectsCategories::getModelClass();
            $strClass = \GeorgPreissl\Projects\ProjectsCategories::getModelClass();
            // $strClass = ProjectsCategories::getModelClass();
			// dump($strClass);
            // $objCategory = $strClass::findPublishedByIdOrAlias(\Input::get($strParam));
            $objCategory = ProjectsCategoryModel::findPublishedByIdOrAlias(\Input::get($strParam));

            if ($objCategory === null) {
                return null;
            }

            $arrCategories = static::getCategoriesCache();
            $arrColumns['category'] = "$t.id IN (" . implode(',', (empty($arrCategories[$objCategory->id]) ? array(0) : $arrCategories[$objCategory->id])) . ")";
        }

        return $arrColumns;
    }

    /**
     * Find published project items by their parent ID
     *
     * ÜBERSCHREIBT DIE GLEICHNAMIGE FUNKTION IN DER PROJECTS-ERWEITERUNG!!!!!!
     *
     * @param array   $arrPids     An array of project archive IDs
     * @param boolean $blnFeatured If true, return only featured project, if false, return only unfeatured project
     * @param integer $intLimit    An optional limit
     * @param integer $intOffset   An optional offset
     * @param array   $arrOptions  An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no project
     */
    public static function findPublishedByPids($arrPids, $blnFeatured=null, $intLimit=0, $intOffset=0, array $arrOptions=array())
    {
        // var_dump("findPublishedByPids");

        if (!is_array($arrPids) || empty($arrPids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if ($blnFeatured === true) {
            $arrColumns[] = "$t.featured=1";
        } elseif ($blnFeatured === false) {
            $arrColumns[] = "$t.featured=''";
        }

        if (!BE_USER_LOGGED_IN) {
            $time = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        // Filter by categories
        $arrColumns = static::filterByCategories($arrColumns);

        // Use the manual sorting from the backend if no sorting has been defined in the module settings
		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = "$t.sorting ASC";
		}

        $arrOptions['limit']  = $intLimit;
        $arrOptions['offset'] = $intOffset;

        // $arrOptions:
        // Array
        // (
        //     [order] => tl_project.sorting ASC
        //     [limit] => 1  <- die Anzahl der Projekte die aufgelistet werden
        //     [offset] => 0
        // )

        // printf('<pre>%s</pre>', print_r($arrColumns,true));
        // $arrColumns:
        // ... sieht so aus wenn die Projekte der Kategorie "Hochbau" aufgelistet werden:
        // Array
        // (
        //     [0] => tl_project.pid IN(1,3,2,4)
        //     [1] => (tl_project.start='' OR tl_project.start<1468769874) AND (tl_project.stop='' OR tl_project.stop>1468769874) AND tl_project.published=1
        //     [category] => tl_project.id IN (6,1,2,7)  <- Die Projekte mit den ID's 6,1,2 und 7 sind in der Kategorie "Hochbau"!
        // )
       
        // $arrColumns['category'] = 'tl_project.id IN (6,1,2,7)';
        // ... wird diese Zeile aktiviert werden immer nur die Projekte mit den IDs 6,1,2 und 7 angezeigt!!!

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count published project items by their parent ID
     *
     * @param array   $arrPids     An array of project archive IDs
     * @param boolean $blnFeatured If true, return only featured project, if false, return only unfeatured project
     * @param array   $arrOptions  An optional options array
     *
     * @return integer The number of project items
     */
    public static function countPublishedByPids($arrPids, $blnFeatured=null, array $arrOptions=array())
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if ($blnFeatured === true) {
            $arrColumns[] = "$t.featured=1";
        } elseif ($blnFeatured === false) {
            $arrColumns[] = "$t.featured=''";
        }

        if (!BE_USER_LOGGED_IN) {
            $time = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        // Filter by categories
        $arrColumns = static::filterByCategories($arrColumns);

        return static::countBy($arrColumns, null, $arrOptions);
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
     * @return \Model\Collection|null A collection of models or null if there are no project
     */
    public static function findPublishedFromToByPids($intFrom, $intTo, $arrPids, $intLimit=0, $intOffset=0, array $arrOptions=array())
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if (!BE_USER_LOGGED_IN) {
            $time = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        // Filter by categories
        $arrColumns = static::filterByCategories($arrColumns);

        if (!isset($arrOptions['order'])) {
            $arrOptions['order']  = "$t.date DESC";
        }

        $arrOptions['limit']  = $intLimit;
        $arrOptions['offset'] = $intOffset;

        return static::findBy($arrColumns, array($intFrom, $intTo), $arrOptions);
    }

    /**
     * Count all published projects items of a certain period of time by their parent ID
     *
     * @param integer $intFrom    The start date as Unix timestamp
     * @param integer $intTo      The end date as Unix timestamp
     * @param array   $arrPids    An array of projects archive IDs
     * @param array   $arrOptions An optional options array
     *
     * @return integer The number of projects items
     */
    public static function countPublishedFromToByPids($intFrom, $intTo, $arrPids, array $arrOptions=array())
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.date>=? AND $t.date<=? AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if (!BE_USER_LOGGED_IN) {
            $time = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        // Filter by categories
        $arrColumns = static::filterByCategories($arrColumns);

        return static::countBy($arrColumns, array($intFrom, $intTo), $arrOptions);
    }

    /**
     * Count all published projects items of a certain category and their parent ID
     *
     * @param array   $arrPids     An array of projects archive IDs
     * @param integer $intCategory The category ID
     * @param array   $arrOptions  An optional options array
     *
     * @return integer The number of projects items
     */
    public static function countPublishedByCategoryAndPids($arrPids, $intCategory=null, array $arrOptions=array())
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if (!BE_USER_LOGGED_IN) {
            $time = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        // Filter by category
        if ($intCategory) {
            $arrCategories = static::getCategoriesCache();

            if ($arrCategories[$intCategory]) {
                $arrColumns[] = "$t.id IN (" . implode(',', $arrCategories[$intCategory]) . ")";
            } else {
                $arrColumns[] = "$t.id=0";
            }
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }



















}
