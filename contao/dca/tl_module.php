<?php


use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Controller;
use Contao\Backend;
use Contao\DataContainer;
use GeorgPreissl\Projects\Security\GeorgPreisslProjectsPermissions;
use Contao\BackendUser;
use Contao\System;

/**
 * Add palettes to tl_module
 */

// list module

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectslist'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_readerModule,numberOfItems,projects_featured,projects_order,skipFirst,perPage;
{projects_related_legend},projects_relatedOnly,projects_includeCurrent,projects_disableEmpty;
{template_legend:hide},projects_template,customTpl;
{image_legend:hide},imgSize;
{categories_legend:hide},projects_filterCategories,projects_filterCategoriesCumulative,projects_filterCategoriesUnion,projects_relatedCategories,projects_includeSubcategories,projects_filterDefault,projects_filterPreserve,projects_categoryFilterPage,projects_categoryImgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID';


// reader module

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsreader']  = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_keepCanonical;
{projects_overview_legend},overviewPage,customLabel;
{projects_sibling_legend},projects_siblingNavigation,projects_siblingShowFirstLast,projects_siblingListModule;
{projects_related_legend},projects_showRelated;
{template_legend:hide},projects_template,customTpl;
{image_legend:hide},imgSize,projects_imgSizeGallery;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';


// archive modules

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsarchive'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_filterCategories,projects_filterDefault,projects_filterPreserve,projects_jumpToCurrent,projects_readerModule,perPage,projects_format;
{template_legend:hide},projects_template,customTpl;
{image_legend:hide},imgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsmenu'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_showQuantityArchives,projects_format,projects_order;
{categories_legend:hide},projects_filterCategories,projects_filterDefault,projects_filterPreserve,projects_showQuantity;
{redirect_legend},jumpTo;
{template_legend:hide},customTpl;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';


// sibling navigation module

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsnavigation'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,siblingShowFirstLast;
{template_legend:hide},customTpl;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space;
{invisible_legend:hide},invisible,start,stop';


// categories modules

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectscategories'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_showQuantity,projects_resetCategories,projects_showEmptyCategories,projects_enableCanonicalUrls,projects_includeSubcategories,showLevel;
{reference_legend:hide},projects_categoriesRoot,projects_customCategories;
{redirect_legend:hide},projects_forceCategoryUrl,jumpTo;
{template_legend:hide},navigationTpl,customTpl;
{image_legend:hide},projects_categoryImgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectscategories_cumulative'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_showQuantity,projects_resetCategories,projects_enableCanonicalUrls,projects_includeSubcategories,projects_filterCategoriesUnion;
{reference_legend:hide},projects_categoriesRoot,projects_customCategories;
{redirect_legend:hide},jumpTo;{template_legend:hide},navigationTpl,customTpl;
{image_legend:hide},projects_categoryImgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectscategories_cumulativehierarchical'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,projects_showQuantity,projects_resetCategories,projects_showEmptyCategories,projects_enableCanonicalUrls,projects_filterCategoriesUnion,projects_includeSubcategories,showLevel;
{reference_legend:hide},projects_categoriesRoot,projects_customCategories;
{redirect_legend:hide},projects_forceCategoryUrl,jumpTo;
{template_legend:hide},navigationTpl,customTpl;
{image_legend:hide},projects_categoryImgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'projects_customCategories';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'projects_relatedCategories';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'projects_showRelated';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['projects_customCategories'] = 'projects_categories';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['projects_relatedCategories'] = 'projects_relatedCategoriesOrder,projects_categoriesRoot';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['projects_showRelated'] = 'projects_includeCurrent,projects_disableEmpty';




/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['projects_archives'] = array
(
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_projects', 'getProjectArchives'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('table'=>'tl_projects_archive', 'type'=>'hasMany', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_featured'] = array
(
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured', 'featured_first'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_jumpToCurrent'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('hide_module', 'show_current', 'all_items'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_readerModule'] = array
(
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getReaderModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_template'] = array
(
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('project_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_format'] = array
(
	'inputType'               => 'select',
	'options'                 => array('project_day', 'project_month', 'project_year'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr', 'submitOnChange'=>true),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'project_month'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_imgSizeGallery'] = array
(
	'inputType'               => 'imageSize',
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
	'options_callback' => static function () {
		return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
	},
	'sql'                     => "varchar(128) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_startDay'] = array
(
	'inputType'               => 'select',
	'options'                 => array(0, 1, 2, 3, 4, 5, 6),
	'reference'               => &$GLOBALS['TL_LANG']['DAYS'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_order'] = array
(
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getSortingOptions'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'order_date_desc'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_showQuantity'] = array
(
	'inputType'               => 'checkbox',
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_showQuantityArchives'] = array
(
	'inputType'               => 'checkbox',
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_keepCanonical'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);


/*
 * Fields for the categories management
 */


$GLOBALS['TL_DCA']['tl_module']['fields']['projects_categories'] = array
(
    'exclude'                 => true,
    'inputType'               => 'picker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => ['multiple'=>true, 'fieldType'=>'checkbox'],
    'sql'                     => ['type' => 'blob', 'notnull' => false],
	'relation'                => ['type' => 'hasMany', 'load' => 'lazy'],
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_customCategories'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterCategoriesCumulative'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterCategoriesCumulative'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class' => 'clr'],
    'sql'                     => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_relatedCategories'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_relatedCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_relatedCategoriesOrder'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_relatedCategoriesOrder'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['default', 'best_match'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['projects_relatedCategoriesOrderRef'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 10, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_includeSubcategories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_includeSubcategories'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterCategoriesUnion'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_enableCanonicalUrls'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_enableCanonicalUrls'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];


$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterDefault'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterDefault'],
    'exclude'                 => true,
    'inputType'               => 'projectsCategoriesPicker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => ['multiple'=>true, 'fieldType'=>'checkbox', 'tl_class'=>'clr'],
    'sql'                     => ['type' => 'blob', 'notnull' => false]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterPreserve'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterPreserve'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_resetCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_resetCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_showEmptyCategories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_showEmptyCategories'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_forceCategoryUrl'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_forceCategoryUrl'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_categoriesRoot'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_categoriesRoot'],
    'exclude'                 => true,
    'inputType'               => 'picker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => array('fieldType'=>'radio','tl_class' => 'clr'),
    'sql'                     => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
	'relation' => ['type' => 'hasMany', 'load' => 'lazy']
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_categoryFilterPage'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_categoryFilterPage'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];


/*
 * Fields for the sibling navigation
 */

 $GLOBALS['TL_DCA']['tl_module']['fields']['projects_siblingNavigation'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['projects_siblingNavigation'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];


$GLOBALS['TL_DCA']['tl_module']['fields']['projects_siblingShowFirstLast'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['siblingShowFirstLast'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_siblingListModule'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getListModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0"
);



/*
 * Fields for the 'show related projects' module
 */

 $GLOBALS['TL_DCA']['tl_module']['fields']['projects_showRelated'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => ['type' => 'boolean', 'default' => 0]
];



 $GLOBALS['TL_DCA']['tl_module']['fields']['projects_relatedOnly'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_disableEmpty'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_includeCurrent'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];






/*
 * Fields for the sibling navigation
 */



$GLOBALS['TL_DCA']['tl_module']['fields']['siblingShowFirstLast'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['siblingShowFirstLast'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];






// $GLOBALS['TL_DCA']['tl_module']['fields']['projects_order']['options_callback'] = function (DataContainer $dc) {
// 	if ($dc->activeRecord && 'sibling_navigation_projects' === $dc->activeRecord->type) {
// 		return ['order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc'];
// 	}

// 	return System::importStatic('tl_module_projects')->getSortingOptions($dc);
	
// };




PaletteManipulator::create()
->addField('projects_order', 'projects_archives', PaletteManipulator::POSITION_AFTER)
->applyToPalette('projectsnavigation', 'tl_module')
;






























/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 */
class tl_module_projects extends Backend
{


	/**
	 * Get all project archives and return them as array
	 *
	 * @return array
	 */
	public function getProjectArchives()
	{
		$user = BackendUser::getInstance();

		if (!$user->isAdmin && !is_array($user->project))
		{
			return array();
		}		

		$arrArchives = array();
		$objArchives = $this->Database->execute("SELECT id, title FROM tl_projects_archive ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objArchives->next())
		{
			if ($security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_EDIT_ARCHIVE, $objArchives->id))
			{
				$arrArchives[$objArchives->id] = $objArchives->title;
			}
		}

		return $arrArchives;
	}


	/**
	 * Get all project reader modules and return them as array
	 *
	 * @return array
	 */
	public function getReaderModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='projectsreader' ORDER BY t.name, m.name");
		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}


	/**
	 * Get all project list modules and return them as array
	 *
	 * @return array
	 */
	public function getListModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='projectslist' ORDER BY t.name, m.name");
		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}



	/**
	 * Return the sorting options
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSortingOptions(DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'projectsmenu')
		{
			return array('order_date_asc', 'order_date_desc');
		}
		
		return array('order_user_asc','order_user_desc','order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc', 'order_random');
	}

}
