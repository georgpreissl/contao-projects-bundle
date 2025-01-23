<?php

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use GeorgPreissl\Projects\Security\GeorgPreisslProjectsPermissions;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;




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
			array('tl_projects_archive', 'adjustDca'),
			// array('tl_projects_archive', 'checkPermission'),
			// array('tl_projects_archive', 'checkCategoryPermission'),
			// array('georgpreissl_project_categories.listener.data_container.projects_archive', 'onLoadCallback')
			// array('tl_projects_archive', 'adjustPalette')
		),
		'oncreate_callback' => array
		(
			array('tl_projects_archive', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_projects_archive', 'adjustPermissions')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_projects_archive', 'addSitemapCacheInvalidationTag'),
		),			
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'jumpTo' => 'index'
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
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'categories' => array
			(
				'href'                => 'table=tl_projects_category',
				'icon'                => 'bundles/georgpreisslprojects/icon.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="c"'
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'test' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio','tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'protected' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),		
		'groups' => array
		(
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),		
		// 'sortOrder' => array
		// (
		// 	'exclude'                 => true,
		// 	'inputType'               => 'select',
		// 	'options'                 => array('ascending', 'descending'),
		// 	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
		// 	'eval'                    => array('tl_class'=>'w50 clr'),
		// 	'sql'                     => "varchar(32) NOT NULL default 'ascending'"
		// ),
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
			'inputType'               => 'picker',
			'foreignKey'              => 'tl_projects_category.title',
			'eval'                    => array(
				'mandatory'=>true, 
				'multiple'=>true, 
				'fieldType'=>'checkbox'
			),
			'sql'                     => ['type' => 'blob', 'notnull' => false],
			'relation'                => ['type' => 'hasMany', 'load' => 'lazy']
		)
	)
);




/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 */
class tl_projects_archive extends Backend
{

	/**
	 * Set the root IDs.
	 */
	public function adjustDca()
	{
		$user = BackendUser::getInstance();
		
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
	}


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

		$user = BackendUser::getInstance();

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

		// The archive is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		$db = Database::getInstance();

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_projects_archive']) && in_array($insertId, $arrNew['tl_projects_archive']))
		{
			// Add the permissions on group level
			if ($user->inherit != 'custom')
			{
				$objGroup = $db->execute("SELECT id, projects, projectsp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next())
				{
					$arrProjectsp = StringUtil::deserialize($objGroup->projectsp);

					if (is_array($arrProjectsp) && in_array('create', $arrProjectsp))
					{
						$arrProjects = StringUtil::deserialize($objGroup->projects, true);
						$arrProjects[] = $insertId;

						$db->prepare("UPDATE tl_user_group SET projects=? WHERE id=?")->execute(serialize($arrProjects), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group')
			{
				$objUser = $db
					->prepare("SELECT projects, projectsp FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrProjectsp = StringUtil::deserialize($objUser->projectsp);

				if (is_array($arrProjectsp) && in_array('create', $arrProjectsp))
				{
					$arrProjects = StringUtil::deserialize($objUser->projects, true);
					$arrProjects[] = $insertId;

					$db->prepare("UPDATE tl_user SET projects=? WHERE id=?")->execute(serialize($arrProjects), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->projects = $root;
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

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}

}
