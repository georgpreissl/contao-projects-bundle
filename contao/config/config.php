<?php


use GeorgPreissl\Projects\FrontendModule\ModuleProjectsArchive;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsList;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsMenu;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsReader;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenu;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenuCumulative;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenuCumulativeHierarchical;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsNavigation;

use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\Model\ProjectsMultilingualModel;
use GeorgPreissl\Projects\Widget\ProjectCategoriesPickerWidget;
use Contao\ListWizard;
use Contao\TableWizard;






/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['content']['projects'] = array
	(
		'tables'      => array('tl_projects_archive', 'tl_projects', 'tl_content', 'tl_projects_category'),
		'table'       => array(TableWizard::class, 'importTable'),
		'list'        => array(ListWizard::class, 'importList')
	);

$GLOBALS['BE_MOD']['design']['projectspdfexport'] = array
	(
		'callback'        => 'GeorgPreissl\Projects\ProjectsExportBe',
		'hideInNavigation' 		  => true
		// 'tables' 		  => array('tl_article'),
		// 'icon'            => 'bundles/georgpreisslcontaogrix/img/icon.svg'
	);

/*
 * Back end form fields
 */
$GLOBALS['BE_FFL']['projectCategoriesPicker'] = ProjectCategoriesPickerWidget::class;


// Front end modules
$GLOBALS['FE_MOD']['projects'] = array
	(
		'projectslist' => ModuleProjectsList::class,
		'projectsreader' => ModuleProjectsReader::class,
		'projectsarchive' => ModuleProjectsArchive::class,
		'projectsmenu' => ModuleProjectsMenu::class,
		'projectscategories' => ModuleProjectsCategoriesMenu::class,
		'projectscategories_cumulative' => ModuleProjectsCategoriesMenuCumulative::class,
		'projectscategories_cumulativehierarchical' => ModuleProjectsCategoriesMenuCumulativeHierarchical::class,
		'projectsnavigation' => ModuleProjectsNavigation::class
	);



// Style sheet
if (defined('TL_MODE') && TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/georgpreisslprojects/css/backend.css|static';

} 

/**
 * Hooks
 */
// $GLOBALS['TL_HOOKS']['getSearchablePages'][] = array(Projects::class, 'getSearchablePages');
// $GLOBALS['TL_HOOKS']['parseArticles'][] = array(Projects::class, 'addProjectCategoriesToTemplate');
// $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array(Projects::class, 'parseCategoriesTags');




// $GLOBALS['TL_HOOKS']['executePostActions'][] = ['georgpreissl_project_categories.listener.ajax', 'onExecutePostActions'];
// $GLOBALS['TL_HOOKS']['projectsListCountItems'][] = ['georgpreissl_project_categories.listener.projects', 'onProjectsListCountItems'];
// $GLOBALS['TL_HOOKS']['projectsListFetchItems'][] = ['georgpreissl_project_categories.listener.projects', 'onProjectsListFetchItems'];
// $GLOBALS['TL_HOOKS']['parseArticles'][] = ['georgpreissl_project_categories.listener.template', 'onParseArticles'];
// $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['georgpreissl_project_categories.listener.insert_tags', 'onReplace'];

// if (false !== ($index = \array_search(['News', 'generateFeeds'], $GLOBALS['TL_HOOKS']['generateXmlFiles'], true))) {
//     $GLOBALS['TL_HOOKS']['generateXmlFiles'][$index][0] = \Codefog\NewsCategoriesBundle\FeedGenerator::class;
// }




// if (in_array('changelanguage', ModuleLoader::getActive())) {
//     $GLOBALS['TL_HOOKS']['translateUrlParameters'][] = array('GeorgPreissl\Projects\ProjectsCategories', 'translateUrlParameters');
// }


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'projects';
$GLOBALS['TL_PERMISSIONS'][] = 'projectsp';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories_default';


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_projects_archive'] = ProjectsArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_projects'] = ProjectsModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category'] = ProjectsCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category_multilingual'] = ProjectsMultilingualModel::class;


/**
 * Content elements
 */
$GLOBALS['TL_CTE']['includes']['projectfilter'] = 'GeorgPreissl\Projects\ContentProjectFilter';

