<?php

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\News;
use GeorgPreissl\Projects\Security\GeorgPreisslProjectsPermissions;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;




/**
 * Table tl_projects_archive
 */
$GLOBALS['TL_DCA']['tl_projects_archive'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_projects'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_projects_archive', 'checkPermission'),
			// array('tl_projects_archive', 'checkCategoryPermission'),
			// array('georgpreissl_project_categories.listener.data_container.projects_archive', 'onLoadCallback')
			// array('tl_projects_archive', 'adjustPalette')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			),
			'categories' => array
			(
				'href'                => 'table=tl_projects_category',
				'icon'                => 'bundles/georgpreisslprojects/icon.png',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="c"'
			)			
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'table=tl_projects',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_projects_archive', 'editHeader')
			),
			'copy' => array
			(
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_projects_archive', 'copyArchive')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_projects_archive', 'deleteArchive')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected','limitCategories'),
		'default'                     => '{title_legend},title,jumpTo;{categories_legend},limitCategories;{protected_legend:hide},protected'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'protected'                   => 'groups',
		'limitCategories'             => 'categories'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio','tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'protected' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'sortOrder' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) NOT NULL default 'ascending'"
		),
		'limitCategories' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => ['type' => 'boolean', 'default' => 0]
		),
		'categories' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'projectCategoriesPicker',
			'foreignKey'              => 'tl_projects_category.title',
			// 'options_callback'        => ['georgpreissl_project_categories.listener.data_container.projects', 'onCategoriesOptionsCallback'],
			'eval'                    => array(
				'mandatory'=>true, 
				'multiple'=>true, 
				'fieldType'=>'checkbox', 
				// 'foreignTable'=>'tl_projects_category', 
				// 'titleField'=>'title', 
				// 'searchField'=>'title', 
				// 'managerHref'=>'do=projects&table=tl_projects_category'
			),
			'sql'                     => ['type' => 'blob', 'notnull' => false]
		)
		// $GLOBALS['TL_DCA']['tl_news_archive']['fields']['categories'] = [
		// 	'label' => &$GLOBALS['TL_LANG']['tl_news_archive']['categories'],
		// 	'exclude' => true,
		// 	'filter' => true,
		// 	'inputType' => 'newsCategoriesPicker',
		// 	'foreignKey' => 'tl_news_category.title',
		// 	'options_callback' => ['codefog_news_categories.listener.data_container.news', 'onCategoriesOptionsCallback'],
		// 	'eval' => ['mandatory' => true, 'multiple' => true, 'fieldType' => 'checkbox'],
		// 	'sql' => ['type' => 'blob', 'notnull' => false],
		// ];
				
	)
);




/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 */
class tl_projects_archive extends Backend
{




	/**
	 * Check permissions to edit table tl_projects_archive
	 */
	public function checkPermission()
	{
		$user = BackendUser::getInstance();

		$bundles = System::getContainer()->getParameter('kernel.bundles');

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->projects) || !is_array($user->projects))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->projects;
		}

		$GLOBALS['TL_DCA']['tl_projects_archive']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

		// Check permissions to add archives
		if (!$security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_CREATE_ARCHIVES))
		{
			$GLOBALS['TL_DCA']['tl_projects_archive']['config']['closed'] = true;
		}

		// Check permissions to delete archives
		if (!$security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_DELETE_ARCHIVES))
		{
			$GLOBALS['TL_DCA']['tl_projects_archive']['config']['notDeletable'] = true;
		}

		$objSession = System::getContainer()->get('session');
		
		// Check current action
		switch (Input::get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_CREATE_ARCHIVES))
				{
					throw new AccessDeniedException('Not enough permissions to create project archives.');
				}
				break;

			case 'edit':
			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_DELETE_ARCHIVES)))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' project archive ID ' . Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'copyAll':
				$session = $objSession->all();

				if (Input::get('act') == 'deleteAll' && !$security->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_DELETE_ARCHIVES))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
				}
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' project archives.');
				}
				break;
		}
	}



	/**
	 * Add the new archive to the permissions
	 *
	 * @param $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->projects) || !is_array($this->User->projects))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->projects;
		}

		// The archive is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_projects_archive']) && in_array($insertId, $arrNew['tl_projects_archive']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, projects, projectsp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrProjetcsp = StringUtil::deserialize($objGroup->projectsp);

					if (is_array($arrProjetcsp) && in_array('create', $arrProjetcsp))
					{
						$arrProjects = StringUtil::deserialize($objGroup->projects, true);
						$arrProjects[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET projects=? WHERE id=?")
									   ->execute(serialize($arrProjects), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT projects, projectsp FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrProjetcsp = StringUtil::deserialize($objUser->projectsp);

				if (is_array($arrProjetcsp) && in_array('create', $arrProjetcsp))
				{
					$arrProjects = StringUtil::deserialize($objUser->projects, true);
					$arrProjects[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET projects=? WHERE id=?")
								   ->execute(serialize($arrProjects), $this->User->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$this->User->projects = $root;
		}
	}




	/**
	 * Return the edit header button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_projects_archive') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}


	/**
	 * Return the copy archive button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function copyArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_CREATE_ARCHIVES) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete archive button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(GeorgPreisslProjectsPermissions::USER_CAN_DELETE_ARCHIVES) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}



    /**
     * Check the permission
     */
    public function checkCategoryPermission()
    {
        $this->import('BackendUser', 'User');

        if (!$this->User->isAdmin && !$this->User->projectscategories) {
            unset($GLOBALS['TL_DCA']['tl_projects_archive']['list']['global_operations']['categories']);
        }
    }

    /**
     * Adjust the palette
     */
    public function adjustPalette($dc=null)
    {
        if (!$dc->id) {
            return;
        }

        $objArchive = $this->Database->prepare("SELECT limitCategories FROM tl_projects_archive WHERE id=?")
                                     ->limit(1)
                                     ->execute($dc->id);

        if (!$objArchive->numRows || !$objArchive->limitCategories) {
            return;
        }
		// dump($objArchive->numRows);
        $GLOBALS['TL_DCA']['tl_projects_archive']['palettes']['default'] = str_replace('limitCategories;', 'limitCategories,categories;', $GLOBALS['TL_DCA']['tl_projects_archive']['palettes']['default']);
    }



}
