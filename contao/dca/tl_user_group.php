<?php

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
$GLOBALS['TL_DCA']['tl_user_group']['palettes']['default'] = str_replace('fop;', 'fop;{projects_legend},projects,newp,projectsfeeds,projectsfeedp;', $GLOBALS['TL_DCA']['tl_user_group']['palettes']['default']);


/**
 * Add fields to tl_user_group
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['projects'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['projects'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_projects_archive.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['newp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['newp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['projectsfeeds'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['projectsfeeds'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_projects_feed.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['projectsfeedp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['projectsfeedp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
