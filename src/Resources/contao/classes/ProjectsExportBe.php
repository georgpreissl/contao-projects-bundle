<?php

/**
 * Space Icon
 * 
 * @copyright  Georg Preissl 2017
 * @package    spaceicon
 * @author     Georg Preissl <http://www.georg-preissl.at>
 * @license    LGPL
 */

namespace GeorgPreissl\Projects;


class ProjectsExportBe extends \BackendModule {


	protected $strTemplate = 'mod_projectsexport';


	public function compile()
	{

		if (TL_MODE=='BE')
		{

			if (!is_array($GLOBALS['TL_JAVASCRIPT']))
			{
				$GLOBALS['TL_JAVASCRIPT'] = array();
			}
		    // add "jQuery.noConflict()" at the beginning of TL_JAVASCRIPT
			array_unshift($GLOBALS['TL_JAVASCRIPT'], 'system/modules/project/assets/js/jquery.noconflict.js');

		    // add the jquery-library at the beginning of TL_JAVASCRIPT
			array_unshift($GLOBALS['TL_JAVASCRIPT'], 'assets/jquery/js/jquery.min.js');

			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/project/assets/js/jspdf.umd.min.js';
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/project/assets/js/script.js';
		}

$id = \Input::get('id');
$db = \Contao\System::getContainer()->get('database_connection');
$result = $db->executeQuery("SELECT * FROM tl_projects WHERE id = ?", [$id])->fetch(); 
		
$this->Template->headline = $result['headline'];
$singleSRC = $result['singleSRC'];

$imagepath = \FilesModel::findByUuid($singleSRC)->path;
$thumbnail = \Image::get($imagepath, 1000, 500, 'crop'); 
//dump($thumbnail);

$this->Template->imagepath = $imagepath;
$this->Template->thumbnail = $thumbnail;
$this->Template->description = $result['description'];

	}





}  




?>