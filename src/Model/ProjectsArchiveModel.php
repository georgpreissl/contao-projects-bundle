<?php


namespace GeorgPreissl\Projects\Model;

use Contao\Model;




/**
 * Reads and writes project archives
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property boolean $protected
 * @property string  $groups
 * @property boolean $allowComments
 * @property string  $notify
 * @property string  $sortOrder
 * @property integer $perPage
 * @property boolean $moderate
 * @property boolean $bbcode
 * @property boolean $requireLogin
 * @property boolean $disableCaptcha
 *
 * @method static \ProjectArchiveModel|null findById($id, $opt=array())
 * @method static \ProjectArchiveModel|null findByPk($id, $opt=array())
 * @method static \ProjectArchiveModel|null findByIdOrAlias($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneBy($col, $val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByTstamp($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByTitle($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByJumpTo($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByProtected($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByGroups($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByAllowComments($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByNotify($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneBySortOrder($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByPerPage($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByModerate($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByBbcode($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByRequireLogin($val, $opt=array())
 * @method static \ProjectArchiveModel|null findOneByDisableCaptcha($val, $opt=array())
 *
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByTstamp($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByTitle($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByJumpTo($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByProtected($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByGroups($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByAllowComments($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByNotify($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findBySortOrder($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByPerPage($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByModerate($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByBbcode($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByRequireLogin($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findByDisableCaptcha($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findMultipleByIds($val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findBy($col, $val, $opt=array())
 * @method static \Model\Collection|\ProjectArchiveModel[]|\ProjectArchiveModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 * @method static integer countByTstamp($val, $opt=array())
 * @method static integer countByTitle($val, $opt=array())
 * @method static integer countByJumpTo($val, $opt=array())
 * @method static integer countByProtected($val, $opt=array())
 * @method static integer countByGroups($val, $opt=array())
 * @method static integer countByAllowComments($val, $opt=array())
 * @method static integer countByNotify($val, $opt=array())
 * @method static integer countBySortOrder($val, $opt=array())
 * @method static integer countByPerPage($val, $opt=array())
 * @method static integer countByModerate($val, $opt=array())
 * @method static integer countByBbcode($val, $opt=array())
 * @method static integer countByRequireLogin($val, $opt=array())
 * @method static integer countByDisableCaptcha($val, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ProjectsArchiveModel extends Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_projects_archive';

}
