<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace GeorgPreissl\Projects\FrontendModule;



/**
 * Front end module "project archive".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleProjectsArchive extends \ModuleProjects
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_projectsarchive';


	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['projectsarchive'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->projects_archives = $this->sortOutProtected(deserialize($this->projects_archives));

		// No project archives available
		if (!is_array($this->projects_archives) || empty($this->projects_archives))
		{
			return '';
		}

		// Show the project reader if an item has been selected
		if ($this->project_readerModule > 0 && (isset($_GET['items']) || (\Config::get('useAutoItem') && isset($_GET['auto_item']))))
		{
			return $this->getFrontendModule($this->project_readerModule, $this->strColumn);
		}

		// Hide the module if no period has been selected
		if ($this->project_jumpToCurrent == 'hide_module' && !isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']))
		{
			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var \PageModel $objPage */
		global $objPage;

		$limit = null;
		$offset = 0;
		$intBegin = 0;
		$intEnd = 0;

		$intYear = \Input::get('year');
		$intMonth = \Input::get('month');
		$intDay = \Input::get('day');

		// Jump to the current period
		if (!isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']) && $this->project_jumpToCurrent != 'all_items')
		{
			switch ($this->project_format)
			{
				case 'project_year':
					$intYear = date('Y');
					break;

				default:
				case 'project_month':
					$intMonth = date('Ym');
					break;

				case 'project_day':
					$intDay = date('Ymd');
					break;
			}
		}

		// Create the date object
		try
		{
			if ($intYear)
			{
				$strDate = $intYear;
				$objDate = new \Date($strDate, 'Y');
				$intBegin = $objDate->yearBegin;
				$intEnd = $objDate->yearEnd;
				$this->headline .= ' ' . date('Y', $objDate->tstamp);
			}
			elseif ($intMonth)
			{
				$strDate = $intMonth;
				$objDate = new \Date($strDate, 'Ym');
				$intBegin = $objDate->monthBegin;
				$intEnd = $objDate->monthEnd;
				$this->headline .= ' ' . \Date::parse('F Y', $objDate->tstamp);
			}
			elseif ($intDay)
			{
				$strDate = $intDay;
				$objDate = new \Date($strDate, 'Ymd');
				$intBegin = $objDate->dayBegin;
				$intEnd = $objDate->dayEnd;
				$this->headline .= ' ' . \Date::parse($objPage->dateFormat, $objDate->tstamp);
			}
			elseif ($this->project_jumpToCurrent == 'all_items')
			{
				$intBegin = 0;
				$intEnd = time();
			}
		}
		catch (\OutOfBoundsException $e)
		{
			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();
			$objHandler->generate($objPage->id);
		}

		$this->Template->articles = array();

		// Split the result
		if ($this->perPage > 0)
		{
			// Get the total number of items
			$intTotal = \ProjectModel::countPublishedFromToByPids($intBegin, $intEnd, $this->projects_archives);

			if ($intTotal > 0)
			{
				$total = $intTotal;

				// Get the current page
				$id = 'page_a' . $this->id;
				$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

				// Do not index or cache the page if the page number is outside the range
				if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
				{
					/** @var \PageError404 $objHandler */
					$objHandler = new $GLOBALS['TL_PTY']['error_404']();
					$objHandler->generate($objPage->id);
				}

				// Set limit and offset
				$limit = $this->perPage;
				$offset = (max($page, 1) - 1) * $this->perPage;

				// Add the pagination menu
				$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
				$this->Template->pagination = $objPagination->generate("\n  ");
			}
		}

		// Get the project items
		if (isset($limit))
		{
			$objArticles = \ProjectModel::findPublishedFromToByPids($intBegin, $intEnd, $this->projects_archives, $limit, $offset);
		}
		else
		{
			$objArticles = \ProjectModel::findPublishedFromToByPids($intBegin, $intEnd, $this->projects_archives);
		}

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseProjects($objArticles);
		}

		$this->Template->headline = trim($this->headline);
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['empty'];
	}
}
