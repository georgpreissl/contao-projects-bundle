<?php

namespace GeorgPreissl\Projects\FrontendModule;

use Contao\System;
use Contao\BackendTemplate;
use Contao\StringUtil;
use Contao\Input;
use Contao\Config;
use Contao\Pagination;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Criteria\ProjectsCriteriaBuilder;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Model\Collection;

class ModuleProjectsList extends ModuleProjects
{


    /**
     * Current project for future reference in search builder
     * @var ProjectsModel
     */
    public $currentProject;

	protected $strTemplate = 'mod_projectslist';



	public function generate()
	{
		
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['projectslist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		
		$this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives));

		// Return if there are no archives
		if (empty($this->projects_archives) || !\is_array($this->projects_archives))
		{
			return '';
		}

		// If list module and reader module are on the same page and an project has been selected
		if ($this->projects_readerModule > 0 && Input::get('auto_item') !== null)
		{
			return $this->getFrontendModule($this->projects_readerModule, $this->strColumn);
		}

        // Generate the list in related categories mode
        if ($this->projects_relatedCategories) {
            return $this->generateRelated();
        }

		return parent::generate();


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
		if ($this->projects_featured == 'featured')
		{
			$blnFeatured = true;
		}
		elseif ($this->projects_featured == 'unfeatured')
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
			$page = (Input::get($id) !== null) ? Input::get($id) : 1;

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
			$objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objProjects = $this->fetchItems($this->projects_archives, $blnFeatured, ($limit ?: 0), $offset);

		// Add the projects
		if ($objProjects !== null)
		{
			$this->Template->projects = $this->parseProjects($objProjects);
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
	protected function countItems($projectArchives, $blnFeatured)
	{
		
        try {
            $criteria = System::getContainer()->get(ProjectsCriteriaBuilder::class)->getCriteriaForListModule($projectArchives, $blnFeatured, $this);
        } catch (CategoryNotFoundException $e) {
            throw new PageNotFoundException($e->getMessage(), 0, $e);
        }

        if (null === $criteria) {
            return 0;
        }

		return ProjectsModel::countBy($criteria->getColumns(), $criteria->getValues());
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
	protected function fetchItems($projectArchives, $blnFeatured, $limit, $offset)
	{

        try {
            $criteria = System::getContainer()->get(ProjectsCriteriaBuilder::class)->getCriteriaForListModule($projectArchives, $blnFeatured, $this);

			/*
			Wenn die Kategorie Türme ausgewählt wurde,
			und zwei Projekte (mit der ID 8 und 14) in dieser Kategorie sind,
			dann sieht $criteria so aus:

			GeorgPreissl\Projects\Criteria\ProjectsCriteria {#2452 ▼
				-columns: array:3 [▼
					0 => "tl_projects.pid IN(1)"
					1 => "tl_projects.published=? AND (tl_projects.start=? OR tl_projects.start<=?) AND (tl_projects.stop=? OR tl_projects.stop>?)"
					2 => "tl_projects.id IN(8,14)"
				]
				-values: array:5 [▼
					0 => 1
					1 => ""
					2 => 1737636060
					3 => ""
					4 => 1737636060
				]
				-options: array:1 [▼
					"order" => "tl_projects.sorting"
				]
				...
				}
			*/


        } catch (CategoryNotFoundException $e) {
            throw new PageNotFoundException($e->getMessage(), 0, $e);
        }
		
        if (null === $criteria) {
            return null;
        }

        $criteria->setLimit($limit);
        $criteria->setOffset($offset);

        return ProjectsModel::findBy(
            $criteria->getColumns(),
            $criteria->getValues(),
            $criteria->getOptions(),
        );		

	}




     /**
     * Generate the list in related categories mode
     *
     * Use the categories of the current news item. The module must be
     * on the same page as news reader module.
     *
     * @return string
     */
    protected function generateRelated()
    {
        $this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives, true));

        // Return if there are no archives
        if (count($this->projects_archives) === 0) {
            return '';
        }

        $alias = Input::get('items') ?: Input::get('auto_item');

        // Return if the project item was not found
        if (($project = ProjectsModel::findPublishedByParentAndIdOrAlias($alias, $this->projects_archives)) === null) {
            return '';
        }

        // Store the project item for further reference
        $this->currentProject = $project;

        return parent::generate();
    }






}
