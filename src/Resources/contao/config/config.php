<?php


use GeorgPreissl\Projects\ModuleProjectsArchive;
use GeorgPreissl\Projects\ModuleProjectsList;
use GeorgPreissl\Projects\ModuleProjectsMenu;
use GeorgPreissl\Projects\ModuleProjectsReader;
use GeorgPreissl\Projects\ModuleProjectsCategories;
use GeorgPreissl\Projects\ModuleProjectsNavigation;

use GeorgPreissl\Projects\ProjectsArchiveModel;
use GeorgPreissl\Projects\ProjectsFeedModel;
use GeorgPreissl\Projects\ProjectsModel;
use GeorgPreissl\Projects\ProjectsCategoryModel;
use GeorgPreissl\Projects\ProjectsMultilingualModel;



/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'projects' => array
	(
		'tables'      => array('tl_projects_archive', 'tl_projects', 'tl_projects_feed', 'tl_content', 'tl_projects_category'),
		'icon'        => 'system/modules/project/assets/icon.png',
		'table'       => array('TableWizard', 'importTable'),
		'list'        => array('ListWizard', 'importList')
	)
));

array_insert($GLOBALS['BE_MOD']['design'], 1, array
(
	'projectspdfexport' => array
	(
		'callback'        => 'GeorgPreissl\Projects\ProjectsExportBe',
		'hideInNavigation' 		  => true
		// 'tables' 		  => array('tl_article'),
		// 'icon'            => 'bundles/georgpreisslcontaogrix/img/icon.svg'
	)


));


/**
 * Front end modules
 */
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'projects' => array
	(
		'projectslist'       => ModuleProjectsList::class,
		'projectsreader'     => ModuleProjectsReader::class,
		'projectsarchive'    => ModuleProjectsArchive::class,
		'projectsmenu'       => ModuleProjectsMenu::class,
		'projectscategories' => ModuleProjectsCategories::class,
		'projectsnavigation' => ModuleProjectsNavigation::class
	)
));



/**
 * Cron jobs
 */
// $GLOBALS['TL_CRON']['daily'][] = array('GeorgPreissl\Projects\Projects', 'generateFeeds');


/**
 * Hooks
 */
// $GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('GeorgPreissl\Projects\Projects', 'purgeOldFeeds');
// $GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array('GeorgPreissl\Projects\Projects', 'generateFeeds');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('GeorgPreissl\Projects\Projects', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['parseArticles'][] = array('GeorgPreissl\Projects\Projects', 'addProjectCategoriesToTemplate');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('GeorgPreissl\Projects\Projects', 'parseCategoriesTags');

if (in_array('changelanguage', \ModuleLoader::getActive())) {
    $GLOBALS['TL_HOOKS']['translateUrlParameters'][] = array('GeorgPreissl\Projects\ProjectsCategories', 'translateUrlParameters');
}


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'projects';
$GLOBALS['TL_PERMISSIONS'][] = 'newp';
$GLOBALS['TL_PERMISSIONS'][] = 'projectsfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'projectsfeedp';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories_default';


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_projects_archive'] = ProjectsArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_projects_feed'] = ProjectsFeedModel::class;
$GLOBALS['TL_MODELS']['tl_projects'] = ProjectsModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category'] = ProjectsCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category_multilingual'] = ProjectsMultilingualModel::class;


/**
 * Content elements
 */
$GLOBALS['TL_CTE']['includes']['projectfilter'] = 'GeorgPreissl\Projects\ContentProjectFilter';


if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/georgpreisslprojects/css/backend.css';

} 