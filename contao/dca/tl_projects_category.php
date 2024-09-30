<?php

use Contao\Config;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Terminal42\DcMultilingualBundle\Driver;

System::loadLanguageFile('tl_projects_archive');

/*
 * Table tl_projects_category
 */
$GLOBALS['TL_DCA']['tl_projects_category'] = [
    // Config
    'config' => [
        // 'label' => $GLOBALS['TL_LANG']['tl_projects_archive']['categories'][0],
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'backlink' => 'do=projects',
        'onload_callback' => [
            // ['georgpreissl_project_categories.listener.data_container.feed', 'onLoadCallback'],
            // ['georgpreissl_project_categories.listener.data_container.projects_category', 'onLoadCallback'],
        ],
        'onsubmit_callback' => [
            // ['georgpreissl_project_categories.listener.data_container.feed', 'onSubmitCallback'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'alias' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_TREE,
            'rootPaste' => true,
            'icon' => 'bundles/georgpreisslprojectscategories/icon.png',
            'panelLayout' => 'filter;search',
        ],
        'label' => [
            'fields' => ['title', 'frontendTitle'],
            'format' => '%s <span style="padding-left:3px;color:#b3b3b3;">[%s]</span>',
            // 'label_callback' => ['georgpreissl_project_categories.listener.data_container.projects_category', 'onLabelCallback'],
        ],
        'global_operations' => [
            'toggleNodes' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['toggleAll'],
                'href' => 'ptg=all',
                'class' => 'header_toggle',
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['copy'],
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'copyChilds' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['copyChilds'],
                'href' => 'act=paste&amp;mode=copy&amp;childs=1',
                'icon' => 'copychilds.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'cut' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['cut'],
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['toggle'],
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{title_legend},title,alias,frontendTitle,cssClass;{details_legend:hide},description,image;{modules_legend:hide},hideInList,hideInReader,excludeInRelated;{redirect_legend:hide},jumpTo;{publish_legend},published',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'frontendTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['frontendTitle'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['alias'],
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'alias',
                'doNotCopy' => true,
                'alwaysSave' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
            ],
            'sql' => ['type' => 'binary', 'length' => 128, 'default' => ''],
        ],        
        'cssClass' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['cssClass'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['description'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'image' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['image'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'files' => true,
                'filesOnly' => true,
                'fieldType' => 'radio',
                'extensions' => \Contao\Config::get('validImageTypes'),
                'tl_class' => 'clr',
            ],
            'sql' => ['type' => 'binary', 'length' => 16, 'notnull' => false],
        ],
        'hideInList' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['hideInList'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => 0],
        ],
        'hideInReader' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['hideInReader'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => 0],
        ],
        'excludeInRelated' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['excludeInRelated'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => 0],
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'eval' => ['fieldType' => 'radio'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_projects_category']['published'],
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            'sql' => ['type' => 'boolean', 'default' => 0],
        ],
    ],
];

/*
 * Enable multilingual features
 */
if (\GeorgPreissl\Projects\MultilingualHelper::isActive()) {
    // Config
    $GLOBALS['TL_DCA']['tl_projects_category']['config']['dataContainer'] = 'Multilingual';
    $GLOBALS['TL_DCA']['tl_projects_category']['config']['langColumn'] = 'language';
    $GLOBALS['TL_DCA']['tl_projects_category']['config']['langPid'] = 'lid';
    $GLOBALS['TL_DCA']['tl_projects_category']['config']['sql']['keys']['language,lid'] = 'index';

    // Fields
    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['language']['sql'] = ['type' => 'string', 'length' => 5, 'default' => ''];
    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['lid']['sql'] = ['type' => 'integer', 'unsigned' => true, 'default' => 0];
    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['title']['eval']['translatableFor'] = '*';
    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['frontendTitle']['eval']['translatableFor'] = '*';
    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['description']['eval']['translatableFor'] = '*';

    $GLOBALS['TL_DCA']['tl_projects_category']['fields']['alias']['eval']['translatableFor'] = '*';
    unset($GLOBALS['TL_DCA']['tl_projects_category']['fields']['alias']['eval']['spaceToUnderscore'], $GLOBALS['TL_DCA']['tl_projects_category']['fields']['alias']['eval']['unique']);
}
