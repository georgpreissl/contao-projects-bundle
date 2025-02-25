<?php


namespace GeorgPreissl\Projects;

use Contao\Date;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Database;
use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\Model\ProjectsModel;

if (class_exists(\Haste\Model\Model::class)) {
	class HasteModel extends \Haste\Model\Model
	{
	}
} else if (class_exists(\Codefog\HasteBundle\Model\DcaRelationsModel::class)){
	class HasteModel extends \Codefog\HasteBundle\Model\DcaRelationsModel
	{
	}
}


class ProjectsSiblingNavigation {








	public static function parseSiblingNavigation($objModule,$objTemplate,$objProject) {

			// Check if archive of current news item is within the enabled archives
			// if (!\in_array($objProject->pid, $objModule->projects_archives, true)) {
			// 	$objModule->projects_archives = [$objProject->pid];
			// }
			
			$t = ProjectsModel::getTable();

			// Basic query definition
			$arrQuery = [
				'column' => [
					"$t.pid IN(".implode(',', array_map('intval', $objModule->projects_archives)).')',
					"$t.id != ?",
				],
				'value' => [$objProject->id],
				'limit' => 1,
				'return' => 'Model',
			];
	
			if (!System::getContainer()->get('contao.security.token_checker')->isPreviewMode()) {
				$time = Date::floorToMinute();
				$arrQuery['column'][] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
			}			


			// Get category parameter
			$strCategory = Input::get('category');
			// dump($strCategory);

			// Check for category input
			if ($strCategory) {
				$arrCategories = StringUtil::trimsplit(',', $strCategory);
				$arrCategoryProjectIds = [];

				// Go through each category
				foreach ($arrCategories as $category) {
					// Get the news items for this category
					$arrProjectIds = HasteModel::getReferenceValues('tl_news', 'categories', $category);

					// Intersect all news IDs (ignoring empty ones)
					if ($arrCategoryProjectIds && $arrProjectIds) {
						$arrCategoryProjectIds = array_intersect($arrCategoryProjectIds, $arrProjectIds);
					} elseif (!$arrCategoryProjectIds) {
						$arrCategoryProjectIds = $arrProjectIds;
					}
				}

				$arrCategoryProjectIds = array_map('intval', $arrCategoryProjectIds);
				$arrCategoryProjectIds = array_filter($arrCategoryProjectIds);
				$arrCategoryProjectIds = array_unique($arrCategoryProjectIds);

				if ($arrCategoryProjectIds) {
					$arrQuery['column'][] = "$t.id IN(".implode(',', $arrCategoryProjectIds).')';
				}
			}

			$arrQueryPrev = $arrQuery;
			$arrQueryNext = $arrQuery;
			$arrQueryFirst = $arrQuery;
			$arrQueryLast = $arrQuery;

			$projectsOrder = "";

			// the list module from which the sorting order is to be adopted
			$idListModule = $objModule->projects_siblingListModule;

			if($idListModule > 0) {
				// the user has selected a list module, so get the order of it
				$result = Database::getInstance()
					->prepare("SELECT projects_order FROM tl_module WHERE id=?")
					->execute($idListModule);				
				$projectsOrder = $result->projects_order;
			}

						
			switch ($projectsOrder) {

				case 'order_user_asc':
					$arrQueryPrev['column'][] = "$t.sorting < ?";
					$arrQueryPrev['value'][] = $objProject->sorting;
					$arrQueryPrev['order'] = "$t.sorting DESC";
	
					$arrQueryNext['column'][] = "$t.sorting > ?";
					$arrQueryNext['value'][] = $objProject->sorting;
					$arrQueryNext['order'] = "$t.sorting ASC";
	
					$arrQueryFirst['column'][] = "$t.sorting < ?";
					$arrQueryFirst['value'][] = $objProject->sorting;
					$arrQueryFirst['order'] = "$t.sorting ASC";
	
					$arrQueryLast['column'][] = "$t.sorting > ?";
					$arrQueryLast['value'][] = $objProject->sorting;
					$arrQueryLast['order'] = "$t.sorting DESC";
					break;					

				case 'order_user_desc':
					$arrQueryPrev['column'][] = "$t.sorting < ?";
					$arrQueryPrev['value'][] = $objProject->sorting;
					$arrQueryPrev['order'] = "$t.sorting DESC";
	
					$arrQueryNext['column'][] = "$t.sorting > ?";
					$arrQueryNext['value'][] = $objProject->sorting;
					$arrQueryNext['order'] = "$t.sorting ASC";
	
					$arrQueryFirst['column'][] = "$t.sorting < ?";
					$arrQueryFirst['value'][] = $objProject->sorting;
					$arrQueryFirst['order'] = "$t.sorting ASC";
	
					$arrQueryLast['column'][] = "$t.sorting > ?";
					$arrQueryLast['value'][] = $objProject->sorting;
					$arrQueryLast['order'] = "$t.sorting DESC";
					break;
				

				case 'sort_date_asc':
				case 'order_date_asc':
					$arrQueryPrev['column'][] = "$t.date > ?";
					$arrQueryPrev['value'][] = $objProject->date;
					$arrQueryPrev['order'] = "$t.date ASC";
	
					$arrQueryNext['column'][] = "$t.date < ?";
					$arrQueryNext['value'][] = $objProject->date;
					$arrQueryNext['order'] = "$t.date DESC";
	
					$arrQueryFirst['column'][] = "$t.date > ?";
					$arrQueryFirst['value'][] = $objProject->date;
					$arrQueryFirst['order'] = "$t.date DESC";
	
					$arrQueryLast['column'][] = "$t.date < ?";
					$arrQueryLast['value'][] = $objProject->date;
					$arrQueryLast['order'] = "$t.date ASC";
					break;
	
				case 'sort_headline_asc':
				case 'order_headline_asc':
					$arrQueryPrev['column'][] = "$t.headline > ?";
					$arrQueryPrev['value'][] = $objProject->headline;
					$arrQueryPrev['order'] = "$t.headline ASC";
	
					$arrQueryNext['column'][] = "$t.headline < ?";
					$arrQueryNext['value'][] = $objProject->headline;
					$arrQueryNext['order'] = "$t.headline DESC";
	
					$arrQueryFirst['column'][] = "$t.headline > ?";
					$arrQueryFirst['value'][] = $objProject->headline;
					$arrQueryFirst['order'] = "$t.headline DESC";
	
					$arrQueryLast['column'][] = "$t.headline < ?";
					$arrQueryLast['value'][] = $objProject->headline;
					$arrQueryLast['order'] = "$t.headline ASC";
					break;
	
				case 'sort_headline_desc':
				case 'order_headline_desc':
					$arrQueryPrev['column'][] = "$t.headline < ?";
					$arrQueryPrev['value'][] = $objProject->headline;
					$arrQueryPrev['order'] = "$t.headline DESC";
	
					$arrQueryNext['column'][] = "$t.headline > ?";
					$arrQueryNext['value'][] = $objProject->headline;
					$arrQueryNext['order'] = "$t.headline ASC";
	
					$arrQueryFirst['column'][] = "$t.headline < ?";
					$arrQueryFirst['value'][] = $objProject->headline;
					$arrQueryFirst['order'] = "$t.headline ASC";
	
					$arrQueryLast['column'][] = "$t.headline > ?";
					$arrQueryLast['value'][] = $objProject->headline;
					$arrQueryLast['order'] = "$t.headline DESC";
					break;
	
				case 'order_custom_date_asc':
					$arrQueryPrev['column'][] = "$t.sorting < ?";
					$arrQueryPrev['value'][] = $objProject->sorting;
					$arrQueryPrev['order'] = "$t.sorting DESC, $t.date ASC";
	
					$arrQueryNext['column'][] = "$t.sorting > ?";
					$arrQueryNext['value'][] = $objProject->sorting;
					$arrQueryNext['order'] = "$t.sorting ASC, $t.date ASC";
	
					$arrQueryFirst['column'][] = "$t.sorting < ?";
					$arrQueryFirst['value'][] = $objProject->sorting;
					$arrQueryFirst['order'] = "$t.sorting ASC, $t.date ASC";
	
					$arrQueryLast['column'][] = "$t.sorting > ?";
					$arrQueryLast['value'][] = $objProject->sorting;
					$arrQueryLast['order'] = "$t.sorting DESC, $t.date ASC";
					break;

				case 'order_custom_date_desc':
					$arrQueryPrev['column'][] = "$t.sorting < ?";
					$arrQueryPrev['value'][] = $objProject->sorting;
					$arrQueryPrev['order'] = "$t.sorting DESC, $t.date DESC";
	
					$arrQueryNext['column'][] = "$t.sorting > ?";
					$arrQueryNext['value'][] = $objProject->sorting;
					$arrQueryNext['order'] = "$t.sorting ASC, $t.date DESC";
	
					$arrQueryFirst['column'][] = "$t.sorting < ?";
					$arrQueryFirst['value'][] = $objProject->sorting;
					$arrQueryFirst['order'] = "$t.sorting ASC, $t.date DESC";
	
					$arrQueryLast['column'][] = "$t.sorting > ?";
					$arrQueryLast['value'][] = $objProject->sorting;
					$arrQueryLast['order'] = "$t.sorting DESC, $t.date DESC";
					break;
	

				default:
					// wenn order_date_desc – d.h. absteigendes Datum
					$arrQueryPrev['column'][] = "$t.date < ?";
					$arrQueryPrev['value'][] = $objProject->date;
					$arrQueryPrev['order'] = "$t.date DESC";
	
					$arrQueryNext['column'][] = "$t.date > ?";
					$arrQueryNext['value'][] = $objProject->date;
					$arrQueryNext['order'] = "$t.date ASC";
	
					$arrQueryFirst['column'][] = "$t.date < ?";
					$arrQueryFirst['value'][] = $objProject->date;
					$arrQueryFirst['order'] = "$t.date ASC"; // von den jüngeren Beiträgen (1,2) der jüngste (ascending - aufsteigend)
	
					$arrQueryLast['column'][] = "$t.date > ?";
					$arrQueryLast['value'][] = $objProject->date;
					$arrQueryLast['order'] = "$t.date DESC"; // von den älteren Beiträgen (4,5) der älteste (descending - absteigend)
					
			}
	// dump($arrQueryPrev);
	// dump($arrQueryNext);
			$objFirst = ProjectsModel::findAll($arrQueryFirst);
			$objLast = ProjectsModel::findAll($arrQueryLast);
			
			
			/*
	
			tl_news.start und tl_news.stop ist bei allen Beiträgen leer
	
			Wenn der Newsbeitrag mit der ID 3 angesehen wird, dann ist $arrQueryPrev zb:
			array:5 [▼
			"column" => array:4 [▼
				0 => "tl_news.pid IN(1)"
				1 => "tl_news.id != ?"
				2 => "tl_news.published='1' AND (tl_news.start='' OR tl_news.start<='1737646020') AND (tl_news.stop='' OR tl_news.stop>'1737646020')"
				3 => "tl_news.date < ?"
			]
			"value" => array:2 [▼
				0 => 3
				1 => 1688885220
			]
			"limit" => 1
			"return" => "Model"
			"order" => "tl_news.date DESC"
			]
	
			Wenn der Newsbeitrag mit der ID 3 angesehen wird, dann ist $arrQueryNext zb:
			array:5 [▼
			"column" => array:4 [▼
				0 => "tl_news.pid IN(1)"
				1 => "tl_news.id != ?"
				2 => "tl_news.published='1' AND (tl_news.start='' OR tl_news.start<='1737645900') AND (tl_news.stop='' OR tl_news.stop>'1737645900')"
				3 => "tl_news.date > ?"
			]
			"value" => array:2 [▼
				0 => 3
				1 => 1688885220
			]
			"limit" => 1
			"return" => "Model"
			"order" => "tl_news.date ASC"
			]
	
	
			Wenn der Newsbeitrag mit der ID 3 angesehen wird, dann ist $arrQueryLast zb:
			array:5 [▼
			"column" => array:4 [▼
				0 => "tl_news.pid IN(1)"
				1 => "tl_news.id != ?"
				2 => "tl_news.published='1' AND (tl_news.start='' OR tl_news.start<='1737645720') AND (tl_news.stop='' OR tl_news.stop>'1737645720')"
				3 => "tl_news.date > ?"
			]
			"value" => array:2 [▼
				0 => 3
				1 => 1688885220 (Info: Der Datenbank-date-Eintrag des Newsbeitrag mit der ID 3 hat den Wert: 1688885220)
			]
			"limit" => 1
			"return" => "Model"
			"order" => "tl_news.date DESC"
			]        
			*/
	
			$objPrev = ProjectsModel::findAll($arrQueryPrev);
			$objNext = ProjectsModel::findAll($arrQueryNext);
	
			$strFirstLink = $objFirst ? Projects::generateProjectUrl($objFirst).($strCategory ? '?category='.$strCategory : '') : null;
			$strLastLink = $objLast ? Projects::generateProjectUrl($objLast).($strCategory ? '?category='.$strCategory : '') : null;
	
			$strPrevLink = $objPrev ? Projects::generateProjectUrl($objPrev).($strCategory ? '?category='.$strCategory : '') : null;
			$strNextLink = $objNext ? Projects::generateProjectUrl($objNext).($strCategory ? '?category='.$strCategory : '') : null;
	
	
			$objTemplate->first = $strFirstLink;
			$objTemplate->last = $strLastLink;
			$objTemplate->prev = $strPrevLink;
			$objTemplate->next = $strNextLink;
			$objTemplate->firstTitle = $objFirst ? $objFirst->headline : '';
			$objTemplate->lastTitle = $objLast ? $objLast->headline : '';
			$objTemplate->prevTitle = $objPrev ? $objPrev->headline : '';
			$objTemplate->nextTitle = $objNext ? $objNext->headline : '';
			$objTemplate->objFirst = $objFirst;
			$objTemplate->objLast = $objLast;
			$objTemplate->objPrev = $objPrev;
			$objTemplate->objNext = $objNext;

		return $objTemplate;
	}



    private function isPreviewMode(): bool
    {
        return System::getContainer()->get('contao.security.token_checker')->isPreviewMode();
    }


}  


