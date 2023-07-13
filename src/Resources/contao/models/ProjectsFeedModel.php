<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace GeorgPreissl\Projects;


/**
 * Reads and writes project feeds
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property string  $alias
 * @property string  $language
 * @property string  $archives
 * @property string  $format
 * @property string  $source
 * @property integer $maxItems
 * @property string  $feedBase
 * @property string  $description
 * @property string  $feedName
 *
 * @method static \ProjectFeedModel|null findById($id, $opt=array())
 * @method static \ProjectFeedModel|null findByPk($id, $opt=array())
 * @method static \ProjectFeedModel|null findByIdOrAlias($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneBy($col, $val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByTstamp($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByTitle($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByAlias($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByLanguage($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByArchives($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByFormat($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneBySource($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByMaxItems($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByFeedBase($val, $opt=array())
 * @method static \ProjectFeedModel|null findOneByDescription($val, $opt=array())
 *
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByTstamp($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByTitle($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByAlias($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByLanguage($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByArchives($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByFormat($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findBySource($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByMaxItems($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByFeedBase($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findByDescription($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findMultipleByIds($val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findBy($col, $val, $opt=array())
 * @method static \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 * @method static integer countByTstamp($val, $opt=array())
 * @method static integer countByTitle($val, $opt=array())
 * @method static integer countByAlias($val, $opt=array())
 * @method static integer countByLanguage($val, $opt=array())
 * @method static integer countByArchives($val, $opt=array())
 * @method static integer countByFormat($val, $opt=array())
 * @method static integer countBySource($val, $opt=array())
 * @method static integer countByMaxItems($val, $opt=array())
 * @method static integer countByFeedBase($val, $opt=array())
 * @method static integer countByDescription($val, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ProjectsFeedModel extends \Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_projects_feed';


	/**
	 * Find all feeds which include a certain project archive
	 *
	 * @param integer $intId      The project archive ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null A collection of models or null if the project archive is not part of a feed
	 */
	public static function findByArchive($intId, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("$t.archives LIKE '%\"" . intval($intId) . "\"%'"), null, $arrOptions);
	}


	/**
	 * Find project feeds by their IDs
	 *
	 * @param array $arrIds     An array of project feed IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|\ProjectFeedModel[]|\ProjectFeedModel|null A collection of models or null if there are no feeds
	 */
	public static function findByIds($arrIds, array $arrOptions=array())
	{
		if (!is_array($arrIds) || empty($arrIds))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.id IN(" . implode(',', array_map('intval', $arrIds)) . ")"), null, $arrOptions);
	}
}
