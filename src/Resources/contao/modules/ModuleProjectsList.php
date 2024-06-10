<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace GeorgPreissl\Projects;


class ModuleProjectsList extends ModuleProjects
{

	protected $strTemplate = 'mod_projectslist';



	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['projectslist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
		
		// $this->projects_archives = $this->sortOutProtected(deserialize($this->projects_archives));

		// // Return if there are no archives
		// if (!is_array($this->projects_archives) || empty($this->projects_archives))
		// {
		// 	return '';
		// }




        // Generate the list in related categories mode
        // not used!
        // if ($this->projects_relatedCategories) {
        //     return $this->generateRelated();
        // }

        // $this->projects_filterCategories);
        // string(1) "1" 

        $GLOBALS['PROJECT_FILTER_CATEGORIES'] = $this->projects_filterCategories ? true : false;
        // bool(true) 

        $GLOBALS['PROJECT_FILTER_DEFAULT']    = deserialize($this->projects_filterDefault, true);
        // array(1) { [0]=> string(1) "5" } 

        $GLOBALS['PROJECT_FILTER_PRESERVE']   = $this->projects_filterPreserve;
        // string(0) "" 


		return parent::generate();

        // Cleanup the $GLOBALS array (see #57)
        unset($GLOBALS['PROJECT_FILTER_CATEGORIES'], $GLOBALS['PROJECT_FILTER_DEFAULT'], $GLOBALS['PROJECT_FILTER_PRESERVE']);

	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{

		$limit = null;
		$offset = intval($this->skipFirst);

		// Maximum number of items
		if ($this->numberOfItems > 0)
		{
			$limit = $this->numberOfItems;
		}

		// Handle featured project
		if ($this->project_featured == 'featured')
		{
			$blnFeatured = true;
		}
		elseif ($this->project_featured == 'unfeatured')
		{
			$blnFeatured = false;
		}
		else
		{
			$blnFeatured = null;
		}

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		// Get the total number of items
		$intTotal = $this->countItems($this->projects_archives, $blnFeatured);
		
		if ($intTotal < 1)
		{
			return;
		}

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				/** @var \PageModel $objPage */
				global $objPage;

				/** @var \PageError404 $objHandler */
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();
				$objHandler->generate($objPage->id);
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = intval($this->skipFirst);

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		// $objProjects = $this->fetchItems($this->projects_archives, $blnFeatured, ($limit ?: 0), $offset);
		$objProjects = $this->fetchItems($blnFeatured, ($limit ?: 0), $offset);

		// Add the articles
		if ($objProjects !== null)
		{
			$this->Template->articles = $this->parseProjects($objProjects);
		}
		
		$this->Template->archives = $this->projects_archives;
				

	}


	/**
	 * Count the total matching items
	 *
	 * @param array $projectArchives
	 * @param boolean $blnFeatured
	 *
	 * @return integer
	 */
	// protected function countItems($projectArchives, $blnFeatured)
	protected function countItems($blnFeatured)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['projectListCountItems']) && is_array($GLOBALS['TL_HOOKS']['projectListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['projectListCountItems'] as $callback)
			{
				if (($intResult = \System::importStatic($callback[0])->{$callback[1]}($projectArchives, $blnFeatured, $this)) === false)
				{
					continue;
				}

				if (is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		// return ProjectsModel::countPublishedByPids($projectArchives, $blnFeatured);
		return ProjectsModel::countPublished($blnFeatured);
	}


	/**
	 * Fetch the matching items
	 *
	 * @param  array   $projectArchives
	 * @param  boolean $blnFeatured
	 * @param  integer $limit
	 * @param  integer $offset
	 *
	 * @return \Model\Collection|\ProjectModel|null
	 */
	// protected function fetchItems($projectArchives, $blnFeatured, $limit, $offset)
	protected function fetchItems($blnFeatured, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['projectListFetchItems']) && is_array($GLOBALS['TL_HOOKS']['projectListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['projectListFetchItems'] as $callback)
			{
				if (($objCollection = \System::importStatic($callback[0])->{$callback[1]}($projectArchives, $blnFeatured, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof \Model\Collection)
				{
					return $objCollection;
				}
			}
		}

		// Determine sorting
		$t = ProjectsModel::getTable();
		$order = '';

		if ($this->projects_featured == 'featured_first')
		{
			$order .= "$t.featured DESC, ";
		}

		switch ($this->projects_order)
		{
			case 'order_user_asc':
				$order .= "$t.sorting";
				break;

			case 'order_user_desc':
				$order .= "$t.sorting DESC";
				break;

			case 'order_headline_asc':
				$order .= "$t.headline";
				break;

			case 'order_headline_desc':
				$order .= "$t.headline DESC";
				break;

			case 'order_random':
				$order .= "RAND()";
				break;

			case 'order_date_asc':
				$order .= "$t.date";
				break;

			default:
				$order .= "$t.date DESC";
		}

		


		// return ProjectsModel::findPublishedByPids($projectArchives, $blnFeatured, $limit, $offset, array('order'=>$order));
		return ProjectsModel::findPublished($blnFeatured, $limit, $offset, array('order'=>$order));
	}




    /**
     * Generate the list in related categories mode
     *
     * Use the categories of the current project item. The module must be
     * on the same page as project reader module.
     *
     * @return string
     */
    protected function generateRelated()
    {
        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item'])) {
            \Input::setGet('items', \Input::get('auto_item'));
        }

        // Return if there is no item specified
        if (!\Input::get('items')) {
            return '';
        }

        $this->projects_archives = $this->sortOutProtected(deserialize($this->projects_archives));

        // Return if there are no archives
        if (!is_array($this->projects_archives) || empty($this->projects_archives)) {
            return '';
        }

        // dump(\Input::get('items'));

        $project = GeorgPreissl\Projects\ProjectsModel::findPublishedByParentAndIdOrAlias(\Input::get('items'), $this->projects_archives);

        // Return if the project item was not found
        if ($project === null) {
            return '';
        }

        $GLOBALS['PROJECT_FILTER_CATEGORIES'] = false;
        $GLOBALS['PROJECT_FILTER_DEFAULT']    = deserialize($project->categories, true);
        $GLOBALS['PROJECT_FILTER_EXCLUDE']    = array($project->id);

        return parent::generate();
    }






}
