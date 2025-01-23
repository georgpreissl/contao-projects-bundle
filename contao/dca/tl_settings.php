<?php


use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_settings']['fields']['projects_categorySlugSetting'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'decodeEntities' => true],
];

PaletteManipulator::create()
    ->addLegend('projects_categories_legend', null, PaletteManipulator::POSITION_AFTER, true)
    ->addField('projects_categorySlugSetting', 'projects_categories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings')
;
