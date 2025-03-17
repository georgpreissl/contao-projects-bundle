<?php

use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Contao\Database;
use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;


System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_projects'] = array
(
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_projects_archive',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'headline',
		'onsubmit_callback' => array
		(
			array('tl_projects', 'adjustTime'),
			// array('georgpreissl_project_categories.listener.data_container.projects', 'onSubmitCallback'),
			// array('tl_projects', 'updateCategories')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_projects', 'addSitemapCacheInvalidationTag'),
		),		
		'onload_callback' => array
		(
			// array('georgpreissl_project_categories.listener.data_container.projects', 'onLoadCallback'),
			// array('tl_projects', 'checkPermission'),
			// array('tl_projects', 'setAllowedCategories')
		),
		'ondelete_callback' => array
		(
			array('tl_projects', 'deleteCategories')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'alias' => 'index',
				'pid,published,featured,start,stop' => 'index'
			)
		)
	),
	'list' => array
	(
		'sorting' => array
		(
			// 'mode'                    => DataContainer::MODE_PARENT,
			// 'fields'                  => array('headline','customer','location'),
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'protected'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('headline', 'customer', 'location', 'dateOfCompletion'),
			'format'                  => '%s <span class="label-info">[%s %s %s]</span>',
			'maxCharacters'           => 60
		),
		'operations' => array
		(
			'edit',
			'children',
			'copy',
			'cut',
			'delete',
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'showInHeader'        => true
			),
			'feature' => array
			(
				'href'                => 'act=toggle&amp;field=featured',
				'icon'                => 'featured.svg',
			),
			'show'
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('source', 'addImage', 'addEnclosure', 'overwriteMeta'),
		'default'                     => ''
									. '{title_legend},headline,featured,alias;'
									. '{category_legend},categories;'
									. '{date_legend},date,time;'
									. '{source_legend:hide},source,linkText,canonicalLink;'
									. '{meta_legend},pageTitle,robots,metaDescription,serpPreview;'
									. '{teaser_legend},subheadline,teaser;'
									. '{image_legend},addImage;'
									. '{gallery_legend},multiSRC,sortBy,galleryFullsize;'
									. '{data_legend},description,customer,location,dateOfCompletion,period;'
									. '{enclosure_legend:hide},addEnclosure;'
									. '{related_legend:hide},relatedProjects;'
									. '{expert_legend:hide},cssClass;'
									. '{publish_legend},published,start,stop',
		'internal'                    => ''
									. '{title_legend},headline,featured,alias;'
									. '{date_legend},date,time;'
									. '{source_legend},source,jumpTo,linkText;'
									. '{teaser_legend},subheadline,teaser;'
									. '{image_legend},addImage;'
									. '{enclosure_legend:hide},addEnclosure;'
									. '{expert_legend:hide},cssClass;'
									. '{publish_legend},published,start,stop',
		'external'                    => ''
									. '{title_legend},headline,featured,alias;'
									. '{date_legend},date,time;'
									. '{source_legend},source,url,target,linkText;'
									. '{teaser_legend},subheadline,teaser;'
									. '{image_legend},addImage;'
									. '{enclosure_legend:hide},addEnclosure;'
									. '{expert_legend:hide},cssClass;'
									. '{publish_legend},published,start,stop'

	),
	// Subpalettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,overwriteMeta',
		'addEnclosure'                => 'enclosure',
		'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_projects_archive.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'sorting' => array
		(
			'sorting' => true,
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),		
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'headline' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'basicEntities'=>true, 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'categories' => array
		(
			'label' => &$GLOBALS['TL_LANG']['tl_projects']['categories'],
			'exclude' => true,
			'filter' => true,
			'inputType' => 'picker',
			'foreignKey' => 'tl_projects_category.title',
			// 'options_callback' => NewsCategoriesOptionsListener
			'eval' => ['multiple' => true, 'fieldType' => 'checkbox'],
			'relation' => [
				'type' => 'haste-ManyToMany',
				'load' => 'lazy',
				'table' => 'tl_projects_category',
				'referenceColumn' => 'project_id',
				'fieldColumn' => 'category_id',
				'relationTable' => 'tl_projects_categories',
			]			
		),
		'featured' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),		
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_projects', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),		
		'date' => array
		(
			'default'                 => time(),
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_BOTH,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('tl_projects', 'loadDate')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),	
		'time' => array
		(
			'default'                 => time(),
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_projects', 'loadTime')
			),			
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),		
		'pageTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'robots' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),	
		'metaDescription' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'exclude'                 => true,
			'inputType'               => 'serpPreview',
			'eval'                    => array(
				// 'url_callback'=>array(
				// 	'tl_projects', 'getSerpUrl'
				// ), 
				'title_tag_callback'  => array('tl_projects', 'getTitleTag'), 
				'titleFields'         => array('pageTitle', 'headline'), 
				'descriptionFields'   => array('metaDescription', 'teaser')
			),
			'sql'                     => null
		),		
		'description' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'canonicalLink' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),		
		'subheadline' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),		
		'addImage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),		
		'overwriteMeta' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		// 'multiSRC' => array
		// (
		// 	'exclude'                 => true,
		// 	'inputType'               => 'fileTree',
		// 	'eval'                    => array(
		// 		'multiple' => true, 
		// 		'fieldType' => 'checkbox', 
		// 		'orderField' => 'orderSRC', 
		// 		'files' => true, 
		// 		'extensions' => Config::get('validImageTypes'),
		// 		'isGallery' => true,
		// 		'tl_class' => 'clr'
		// 	),
		// 	'sql'                     => "blob NULL"
		// ),
		'multiSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'isSortable' => true, 'files'=>true,'isGallery' => true,'extensions' => Config::get('validImageTypes')),
			'sql'                     => "blob NULL"
			// 'load_callback' => array
			// (
			// 	array('tl_projects', 'setMultiSrcFlags')
			// )
		),			
		'orderSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
			'sql'                     => "blob NULL"
		),
		// 'sortBy' => array
		// (
		// 	'exclude'                 => true,
		// 	'inputType'               => 'select',
		// 	'options'                 => array('custom', 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
		// 	'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
		// 	'eval'                    => array('tl_class'=>'w50 clr'),
		// 	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		// ),
		'galleryFullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'sortBy' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('custom', 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'customer' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'dateOfCompletion' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_BOTH,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),		
			'sql'                     => "varchar(10) NOT NULL default ''"
		),			
		'period' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),		
		'location' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),		
		'addEnclosure' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'enclosure' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'isDownloads'=>true, 'extensions'=>Config::get('allowedDownload'), 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'source' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'radio',
			'options_callback'        => array('tl_projects', 'getSourceOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_projects'],
			'eval'                    => array('submitOnChange'=>true, 'helpwizard'=>true),
			'sql'                     => "varchar(12) NOT NULL default 'default'"
		),
		'linkText' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),		
		'jumpTo' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'articleId' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_projects', 'getArticleAlias'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('table'=>'tl_article', 'type'=>'hasOne', 'load'=>'lazy'),
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'relatedProjects' => array
		(
			'exclude'                 => true,
			'inputType'               => 'picker',
			'eval'                    => ['multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
			'relation'                => ['type' => 'belongsToMany', 'load' => 'lazy', 'table' => 'tl_projects'],
			'sql'                     => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
		),	
		'cssClass' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'published' => array
		(
			'exclude'                 => true,
			'toggle'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)

	)
);





/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 */
class tl_projects extends Backend
{



	/**
	 * Check permissions to edit table tl_projects
	 */
	public function checkPermission()
	{
		$user = BackendUser::getInstance();
		$bundles = System::getContainer()->getParameter('kernel.bundles');

		if ($user->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($user->projects) || !is_array($user->projects))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->projects;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check CURRENT_ID here (see #247)
				if (!in_array(CURRENT_ID, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access projects archive ID ' . $id . '.');
				}
				break;

			case 'create':
				if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
				{
					throw new AccessDeniedException('Not enough permissions to create project items in project archive ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (Input::get('act') == 'cut' && Input::get('mode') == 1)
				{
					$objArchive = $this->Database->prepare("SELECT pid FROM tl_projects WHERE id=?")
												 ->limit(1)
												 ->execute(Input::get('pid'));

					if ($objArchive->numRows < 1)
					{
						throw new AccessDeniedException('Invalid project item ID ' . Input::get('pid') . '.');
					}

					$pid = $objArchive->pid;
				}
				else
				{
					$pid = Input::get('pid');
				}

				if (!in_array($pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' project item ID ' . $id . ' to news archive ID ' . $pid . '.');
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objArchive = $this->Database->prepare("SELECT pid FROM tl_projects WHERE id=?")
											 ->limit(1)
											 ->execute($id);

				if ($objArchive->numRows < 1)
				{
					throw new AccessDeniedException('Invalid project item ID ' . $id . '.');
				}

				if (!in_array($objArchive->pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' project item ID ' . $id . ' of news archive ID ' . $objArchive->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access project archive ID ' . $id . '.');
				}

				$objArchive = $this->Database->prepare("SELECT id FROM tl_projects WHERE pid=?")
											 ->execute($id);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access project archive ID ' . $id . '.');
				}
				break;
		}
	}


	/**
	 * Auto-generate the news alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool
		{
			return $this->Database->prepare("SELECT id FROM tl_projects WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->headline, ProjectsArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);

		}
		elseif (preg_match('/^[1-9]\d*$/', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}




	/**
	 * Set the timestamp to 00:00:00 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadDate($value)
	{
		return strtotime(date('Y-m-d', $value) . ' 00:00:00');
	}

	/**
	 * Set the timestamp to 1970-01-01 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadTime($value)
	{
		return strtotime('1970-01-01 ' . date('H:i:s', $value));
	}


	/**
	 * Return the SERP URL
	 *
	 * @param ProjectsModel $model
	 *
	 * @return string
	 */
	public function getSerpUrl()
	{
		// return Projects::generateProjectUrl($model, false, true);
	}

	/**
	 * Return the title tag from the associated page layout
	 *
	 * @param ProjectsModel $model
	 *
	 * @return string
	 */
	public function getTitleTag(ProjectsModel $model)
	{
		/** @var Model\ $archive */
		if (!$archive = $model->getRelated('pid'))
		{
			return '';
		}

		/** @var PageModel $page */
		if (!$page = $archive->getRelated('jumpTo'))
		{
			return '';
		}

		$page->loadDetails();

		/** @var LayoutModel $layout */
		if (!$layout = $page->getRelated('layout'))
		{
			return '';
		}

		$origObjPage = $GLOBALS['objPage'] ?? null;

		// Override the global page object, so we can replace the insert tags
		$GLOBALS['objPage'] = $page;

		$title = implode(
			'%s',
			array_map(
				static function ($strVal)
				{
					return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
				},
				explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
			)
		);

		$GLOBALS['objPage'] = $origObjPage;

		return $title;
	}



	/**
	 * Get all articles and return them as array
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getArticleAlias(DataContainer $dc)
	{
		$arrPids = array();
		$arrAlias = array();

		if (!$this->User->isAdmin)
		{
			foreach ($this->User->pagemounts as $id)
			{
				$arrPids[] = $id;
				$arrPids = array_merge($arrPids, $this->Database->getChildRecords($id, 'tl_page'));
			}

			if (empty($arrPids))
			{
				return $arrAlias;
			}

			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(". implode(',', array_map('intval', array_unique($arrPids))) .") ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}
		else
		{
			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}

		if ($objAlias->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objAlias->next())
			{
				$arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
			}
		}

		return $arrAlias;
	}


	/**
	 * Add the source options depending on the allowed fields (see #5498)
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSourceOptions(DataContainer $dc)
	{



		if (BackendUser::getInstance()->isAdmin)
		{
			return array('default', 'internal', 'external');
		}

		$security = System::getContainer()->get('security.helper');
		$arrOptions = array('default');

		// Add the "internal" option
		if (
			$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_projects::jumpTo')
			&& $security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'page')
		) {
			$arrOptions[] = 'internal';
		}

		// Add the "external" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_projects::url'))
		{
			$arrOptions[] = 'external';
		}

		// Add the option currently set
		if ($dc->activeRecord?->source)
		{
			$arrOptions[] = $dc->activeRecord->source;
			$arrOptions = array_unique($arrOptions);
		}

		return $arrOptions;
	}


	/**
	 * Adjust start end end time of the event based on date, span, startTime and endTime
	 *
	 * @param DataContainer $dc
	 */
	public function adjustTime(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		$arrSet['date'] = strtotime(date('Y-m-d', $dc->activeRecord->date) . ' ' . date('H:i:s', $dc->activeRecord->time));
		$arrSet['time'] = $arrSet['date'];

		// $this->Database->prepare("UPDATE tl_projects %s WHERE id=?")->set($arrSet)->execute($dc->id);
		Database::getInstance()->prepare("UPDATE tl_projects %s WHERE id=?")->set($arrSet)->execute($dc->id);
	}

	/**
	 * Add the type of input field
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listProjectArticles($arrRow)
	{
		return '<div class="tl_content_left">' . $arrRow['headline'] . ' <span style="color:#b3b3b3;padding-left:3px">[' . Date::parse(Config::get('datimFormat'), $arrRow['date']) . ']</span></div>';
	}







    /**
     * Set the allowed categories
     * @param DataContainer
     */
    public function setAllowedCategories($dc=null)
    {
		// dump($dc->id);
        if (!$dc->id) {
            return;
        }

        $objArchive = $this->Database->prepare("SELECT categories FROM tl_projects_archive WHERE limitCategories=1 AND id=(SELECT pid FROM tl_projects WHERE id=?)")
                                     ->limit(1)
                                     ->execute($dc->id);
									 
        if (!$objArchive->numRows) {
            return;
        }
		
        $arrCategories = deserialize($objArchive->categories, true);
		// dump($arrCategories);
        if (empty($arrCategories)) {
            return;
        }
		
        $GLOBALS['TL_DCA']['tl_projects']['fields']['categories']['rootNodes'] = $arrCategories;
    }

    /**
     * Update the category relations
     * @param DataContainer
     */
    public function updateCategories(DataContainer $dc)
    {
        $this->import('BackendUser', 'User');
        $arrCategories = deserialize($dc->activeRecord->categories);

        // Use the default categories if the user is not allowed to edit the field directly
        if (!$this->User->isAdmin && !in_array('tl_projects::categories', $this->User->alexf)) {

            // Return if the record is not new
            if ($dc->activeRecord->tstamp) {
                return;
            }

            $arrCategories = $this->User->projectscategories_default;
        }

        $this->deleteCategories($dc);

        if (is_array($arrCategories) && !empty($arrCategories)) {
            foreach ($arrCategories as $intCategory) {
                $this->Database->prepare("INSERT INTO tl_projects_categories (category_id, project_id) VALUES (?, ?)")
                               ->execute($intCategory, $dc->id);
            }

            $this->Database->prepare("UPDATE tl_projects SET categories=? WHERE id=?")
                           ->execute(serialize($arrCategories), $dc->id);
        }
    }

    /**
     * Delete the category relations
     * @param DataContainer
     */
    public function deleteCategories(DataContainer $dc)
    {
        $this->Database->prepare("DELETE FROM tl_projects_categories WHERE project_id=?")
                       ->execute($dc->id);
    }





	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$archiveModel = ProjectsArchiveModel::findById($dc->activeRecord->pid);

		if ($archiveModel === null)
		{
			return $tags;
		}

		$pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}





	/**
	 * Dynamically add flags to the "multiSRC" field
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setMultiSrcFlags($varValue, DataContainer $dc)
	{
		dump($dc->activeRecord->type);
		if ($dc->activeRecord)
		{
			dump($dc->activeRecord->type);
			switch ($dc->activeRecord->type)
			{
				case 'gallery':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isGallery'] = true;
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = '%contao.image.valid_extensions%';
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = !$dc->activeRecord->useHomeDir;
					break;

				case 'downloads':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isDownloads'] = true;
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Config::get('allowedDownload');
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = !$dc->activeRecord->useHomeDir;
					break;
			}
		}

		return $varValue;
	}






}
