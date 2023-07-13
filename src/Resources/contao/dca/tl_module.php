<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectslist']    = '
{title_legend},name,headline,type;
{config_legend},projects_archives,numberOfItems,projects_featured,projects_order,perPage,skipFirst;
{template_legend:hide},projects_metaFields,projects_template,customTpl;
{image_legend:hide},imgSize;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsreader']  = '{title_legend},name,headline,type;{config_legend},projects_archives;{template_legend:hide},projects_metaFields,projects_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsarchive'] = '{title_legend},name,headline,type;{config_legend},projects_archives,projects_jumpToCurrent,projects_readerModule,perPage,projects_format;{template_legend:hide},projects_metaFields,projects_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsmenu']    = '{title_legend},name,headline,type;{config_legend},projects_archives,projects_showQuantity,projects_format,projects_startDay,projects_order;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsnavigation'] = '
{title_legend},name,headline,type;
{config_legend},projects_archives,siblingShowFirstLast;
{template_legend:hide},customTpl;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space;
{invisible_legend:hide},invisible,start,stop';

/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['projects_archives'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_archives'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_projects', 'getProjectArchives'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_featured'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_featured'],
	'default'                 => 'all_items',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_jumpToCurrent'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_jumpToCurrent'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('hide_module', 'show_current', 'all_items'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_readerModule'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_readerModule'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getReaderModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_metaFields'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_metaFields'],
	'default'                 => array('date', 'author'),
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('date', 'author', 'comments'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_template'],
	'default'                 => 'project_latest',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getProjectTemplates'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_format'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_format'],
	'default'                 => 'project_month',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('project_day', 'project_month', 'project_year'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'wizard' => array
	(
		array('tl_module_projects', 'hideStartDay')
	),
	'sql'                     => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_startDay'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_startDay'],
	'default'                 => 0,
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array(0, 1, 2, 3, 4, 5, 6),
	'reference'               => &$GLOBALS['TL_LANG']['DAYS'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_order'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_order'],
	'default'                 => 'descending',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_projects', 'getSortingOptions'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_showQuantity'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['project_showQuantity'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'sql'                     => "char(1) NOT NULL default ''"
);


/**
 * Add the comments template drop-down menu
 */
if (in_array('comments', ModuleLoader::getActive()))
{
	$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsreader'] = str_replace('{protected_legend:hide}', '{comment_legend:hide},com_template;{protected_legend:hide}', $GLOBALS['TL_DCA']['tl_module']['palettes']['projectsreader']);
}














/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'projects_customCategories';
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectscategories'] = '{title_legend},name,headline,type;{config_legend},projects_archives,projects_resetCategories,projects_showQuantity,projects_categoriesRoot,projects_customCategories;{redirect_legend:hide},jumpTo;{template_legend:hide},navigationTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['projects_customCategories'] = 'projects_categories';

/**
 * Extend tl_module palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectslist'] = str_replace('projects_archives,', 'projects_archives,projects_filterCategories,projects_relatedCategories,projects_filterDefault,projects_filterPreserve,', $GLOBALS['TL_DCA']['tl_module']['palettes']['projectslist']);
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsarchive'] = str_replace('projects_archives,', 'projects_archives,projects_filterCategories,projects_filterDefault,projects_filterPreserve,', $GLOBALS['TL_DCA']['tl_module']['palettes']['projectsarchive']);
$GLOBALS['TL_DCA']['tl_module']['palettes']['projectsmenu'] = str_replace('projects_archives,', 'projects_archives,projects_filterCategories,projects_filterDefault,projects_filterPreserve,', $GLOBALS['TL_DCA']['tl_module']['palettes']['projectsmenu']);

/**
 * Add new fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['projects_categories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_categories'],
    'exclude'                 => true,
    'inputType'               => 'treePicker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'foreignTable'=>'tl_projects_category', 'titleField'=>'title', 'searchField'=>'title', 'managerHref'=>'do=projects&table=tl_projects_category'),
    'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_customCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_customCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_relatedCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_relatedCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterDefault'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterDefault'],
    'exclude'                 => true,
    'inputType'               => 'treePicker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'foreignTable'=>'tl_projects_category', 'titleField'=>'title', 'searchField'=>'title', 'managerHref'=>'do=projects&table=tl_projects_category', 'tl_class'=>'clr'),
    'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_filterPreserve'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_filterPreserve'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_resetCategories'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_resetCategories'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['projects_categoriesRoot'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['projects_categoriesRoot'],
    'exclude'                 => true,
    'inputType'               => 'treePicker',
    'foreignKey'              => 'tl_projects_category.title',
    'eval'                    => array('fieldType'=>'radio', 'foreignTable'=>'tl_projects_category', 'titleField'=>'title', 'searchField'=>'title', 'managerHref'=>'do=projects&table=tl_projects_category'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

















$GLOBALS['TL_DCA']['tl_module']['fields']['siblingShowFirstLast'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['siblingShowFirstLast'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];



$GLOBALS['TL_DCA']['tl_module']['fields']['projects_order']['options_callback'] = function (DataContainer $dc) {
	if ($dc->activeRecord && 'sibling_navigation_projects' === $dc->activeRecord->type) {
		return ['order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc'];
	}

	return System::importStatic('tl_module_projects')->getSortingOptions($dc);
	
};

PaletteManipulator::create()
->addField('projects_order', 'projects_archives', PaletteManipulator::POSITION_AFTER)
->applyToPalette('projectsnavigation', 'tl_module')
;






























/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_module_projects extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}


	/**
	 * Get all project archives and return them as array
	 *
	 * @return array
	 */
	public function getProjectArchives()
	{
		if (!$this->User->isAdmin && !is_array($this->User->project))
		{
			return array();
		}

		$arrArchives = array();
		$objArchives = $this->Database->execute("SELECT id, title FROM tl_projects_archive ORDER BY title");

		while ($objArchives->next())
		{
			if ($this->User->hasAccess($objArchives->id, 'project'))
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
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='projectreader' ORDER BY t.name, m.name");

		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}


	/**
	 * Hide the start day drop-down if not applicable
	 *
	 * @return string
	 */
	public function hideStartDay()
	{
		return '
  <script>
    var enableStartDay = function() {
      var e1 = $("ctrl_project_startDay").getParent("div");
      var e2 = $("ctrl_project_order").getParent("div");
      if ($("ctrl_project_format").value == "project_day") {
        e1.setStyle("display", "block");
        e2.setStyle("display", "none");
	  } else {
        e1.setStyle("display", "none");
        e2.setStyle("display", "block");
	  }
    };
    window.addEvent("domready", function() {
      if ($("ctrl_project_startDay")) {
        enableStartDay();
        $("ctrl_project_format").addEvent("change", enableStartDay);
      }
    });
  </script>';
	}


	/**
	 * Return all project templates as array
	 *
	 * @return array
	 */
	public function getProjectTemplates()
	{
		return $this->getTemplateGroup('project_');
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
