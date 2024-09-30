<?php


use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('projectCategories_legend', 'global_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('projectCategories_param', 'projectCategories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('root', 'tl_page')
    ->applyToPalette('rootfallback', 'tl_page')
;

// if (isset($GLOBALS['TL_DCA']['tl_page']['palettes']['rootfallback'])) {
//     $palette->applyToPalette('rootfallback', 'tl_page');
// }


// PaletteManipulator::create()
//     ->addField(['newsCategories', 'newsCategories_show'], 'newsArchives')
//     ->applyToPalette('news_feed', 'tl_page')
// ;



/*
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['projectCategories_param'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['projectCategories_param'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'rgxp' => 'alias', 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
];
