<?php

use GeorgPreissl\Projects\FrontendModule\ModuleProjectsList;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsReader;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsMenu;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsArchive;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenu;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenuCumulative;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsCategoriesMenuCumulativeHierarchical;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsNavigation;

use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\Model\ProjectsMultilingualModel;
use GeorgPreissl\Projects\Widget\ProjectCategoriesPickerWidget;
use Contao\System;
use Contao\ListWizard;
use Contao\TableWizard;
use Symfony\Component\HttpFoundation\Request;


// Back end modules
$GLOBALS['BE_MOD']['content']['projects'] = array
(
	'tables'      => array('tl_projects_archive', 'tl_projects', 'tl_content', 'tl_projects_category'),
	'table'       => array(BackendCsvImportController::class, 'importTableWizardAction'),
	'list'        => array(BackendCsvImportController::class, 'importListWizardAction')	
);
$GLOBALS['BE_MOD']['design']['projectspdfexport'] = array
(
	'callback'         => 'GeorgPreissl\Projects\ProjectsExportBe',
	'hideInNavigation' => true
	// 'tables' 		 => array('tl_article'),
	// 'icon'            => 'bundles/georgpreisslcontaogrix/img/icon.svg'
);

// Back end form fields
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
if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
    $GLOBALS['TL_CSS'][] = 'bundles/georgpreisslprojects/css/backend.css|static';
} 

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'projects';
$GLOBALS['TL_PERMISSIONS'][] = 'projectsp';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories';
$GLOBALS['TL_PERMISSIONS'][] = 'projectscategories_default';

// Models
$GLOBALS['TL_MODELS']['tl_projects_archive'] = ProjectsArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_projects'] = ProjectsModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category'] = ProjectsCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_projects_category_multilingual'] = ProjectsMultilingualModel::class;

