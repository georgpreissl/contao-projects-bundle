<?php


use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palettes
PaletteManipulator::create()
	->addLegend('projects_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('projects', 'projectsp'), 'projects_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;



/**
 * Add fields to tl_user_group
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['projects'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['projects'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_projects_archive.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['projectsp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['projectsp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);



/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Extend default palette
 */
// $GLOBALS['TL_DCA']['tl_user']['palettes']['extend'] = str_replace('fop;', 'fop;{projects_legend},projects,newp,projectsfeeds,projectsfeedp;', $GLOBALS['TL_DCA']['tl_user']['palettes']['extend']);
// $GLOBALS['TL_DCA']['tl_user']['palettes']['custom'] = str_replace('fop;', 'fop;{projects_legend},projects,newp,projectsfeeds,projectsfeedp;', $GLOBALS['TL_DCA']['tl_user']['palettes']['custom']);

