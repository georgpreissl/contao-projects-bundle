<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoSiblingNavigationBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace GeorgPreissl\Projects;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Date;
use Contao\Input;
use Contao\ModuleNews;
use Contao\News;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Haste\Model\Model as HasteModel;

class ModuleProjectsNavigation extends ModuleProjects
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_projectsnavigation';

    /**
     * Current news item.
     *
     * @var NewsModel
     */
    protected $currentProject;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD']['sibling_navigation_news'][0]).' ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        $items = Input::get('items', false, true);
        $autoItem = Input::get('auto_item', false, true);

        if (null === $items && false !== Config::get('useAutoItem') && null !== $autoItem) {
            $item = $autoItem;
        } else {
            $item = $items;
        }

        if (null === $item) {
            return '';
        }

        $this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives, true));

        $this->currentProject = ProjectsModel::findByIdOrAlias($item);

        if (null === $this->currentProject) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        // Check if archive of current project is within the enabled archives
        if (!\in_array($this->currentProject->pid, $this->projects_archives, true)) {
            $this->projects_archives = [$this->currentProject->pid];
        }
        // dump(deserialize($this->projects_archives));
        
        $t = ProjectsModel::getTable();
        // dump($t);
        
        // Basic query definition
        $arrQuery = [
            'column' => [
                "$t.pid IN(".implode(',', array_map('intval', $this->projects_archives)).')',
                "$t.id != ?",
            ],
            'value' => [$this->currentProject->id],
            'limit' => 1,
            'return' => 'Model',
        ];

        if (!$this->isPreviewMode()) {
            $time = Date::floorToMinute();
            $arrQuery['column'][] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        // Get category parameter
        $strCategory = Input::get('category');

        // Check for category input
        $bundles = System::getContainer()->getParameter('kernel.bundles');
        if ($strCategory && \in_array(ModuleProjectsCategories::class, $bundles, true)) {
            $arrCategories = StringUtil::trimsplit(',', $strCategory);
            $arrCategoryProjectIds = [];

            // Go through each category
            foreach ($arrCategories as $category) {
                // Get the news items for this category
                $arrProjectsIds = HasteModel::getReferenceValues('tl_projects', 'categories', $category);

                // Intersect all news IDs (ignoring empty ones)
                if ($arrCategoryProjectIds && $arrProjectsIds) {
                    $arrCategoryProjectIds = array_intersect($arrCategoryProjectIds, $arrProjectsIds);
                } elseif (!$arrCategoryProjectIds) {
                    $arrCategoryProjectIds = $arrProjectsIds;
                }
            }

            $arrCategoryProjectIds = array_map('intval', $arrCategoryProjectIds);
            $arrCategoryProjectIds = array_filter($arrCategoryProjectIds);
            $arrCategoryProjectIds = array_unique($arrCategoryProjectIds);

            if ($arrCategoryProjectIds) {
                $arrQuery['column'][] = "$t.id IN(".implode(',', $arrCategoryProjectIds).')';
            }
        }

        // $this->projects_order;
        // The type of sorting selected in the FE module 'ProjectsNavigation', eg.: 'order_random'

        //dump($this->projects_order);

        $arrQueryPrev = $arrQuery;
        $arrQueryNext = $arrQuery;
        $arrQueryFirst = $arrQuery;
        $arrQueryLast = $arrQuery;

        // support for news_sorting and news_order
        // $this->news_order = $this->news_sorting ?: $this->news_order;
        switch ($this->projects_order) {

            case 'order_user_asc':
                $arrQueryPrev['column'][] = "$t.sorting < ?";
                $arrQueryPrev['value'][] = $this->currentProject->sorting;
                $arrQueryPrev['order'] = "$t.sorting DESC";

                $arrQueryNext['column'][] = "$t.sorting > ?";
                $arrQueryNext['value'][] = $this->currentProject->sorting;
                $arrQueryNext['order'] = "$t.sorting ASC";

                $arrQueryFirst['column'][] = "$t.sorting < ?";
                $arrQueryFirst['value'][] = $this->currentProject->sorting;
                $arrQueryFirst['order'] = "$t.sorting ASC";

                $arrQueryLast['column'][] = "$t.sorting > ?";
                $arrQueryLast['value'][] = $this->currentProject->sorting;
                $arrQueryLast['order'] = "$t.sorting DESC";
                break;

            case 'order_user_desc':
                $arrQueryPrev['column'][] = "$t.sorting < ?";
                $arrQueryPrev['value'][] = $this->currentProject->sorting;
                $arrQueryPrev['order'] = "$t.sorting DESC";

                $arrQueryNext['column'][] = "$t.sorting > ?";
                $arrQueryNext['value'][] = $this->currentProject->sorting;
                $arrQueryNext['order'] = "$t.sorting ASC";

                $arrQueryFirst['column'][] = "$t.sorting < ?";
                $arrQueryFirst['value'][] = $this->currentProject->sorting;
                $arrQueryFirst['order'] = "$t.sorting ASC";

                $arrQueryLast['column'][] = "$t.sorting > ?";
                $arrQueryLast['value'][] = $this->currentProject->sorting;
                $arrQueryLast['order'] = "$t.sorting DESC";
                break;


            case 'sort_date_asc':
            case 'order_date_asc':
                $arrQueryPrev['column'][] = "$t.date > ?";
                $arrQueryPrev['value'][] = $this->currentProject->date;
                $arrQueryPrev['order'] = "$t.date ASC";

                $arrQueryNext['column'][] = "$t.date < ?";
                $arrQueryNext['value'][] = $this->currentProject->date;
                $arrQueryNext['order'] = "$t.date DESC";

                $arrQueryFirst['column'][] = "$t.date > ?";
                $arrQueryFirst['value'][] = $this->currentProject->date;
                $arrQueryFirst['order'] = "$t.date DESC";

                $arrQueryLast['column'][] = "$t.date < ?";
                $arrQueryLast['value'][] = $this->currentProject->date;
                $arrQueryLast['order'] = "$t.date ASC";
                break;

            case 'sort_headline_asc':
            case 'order_headline_asc':
                $arrQueryPrev['column'][] = "$t.headline > ?";
                $arrQueryPrev['value'][] = $this->currentProject->headline;
                $arrQueryPrev['order'] = "$t.headline ASC";

                $arrQueryNext['column'][] = "$t.headline < ?";
                $arrQueryNext['value'][] = $this->currentProject->headline;
                $arrQueryNext['order'] = "$t.headline DESC";

                $arrQueryFirst['column'][] = "$t.headline > ?";
                $arrQueryFirst['value'][] = $this->currentProject->headline;
                $arrQueryFirst['order'] = "$t.headline DESC";

                $arrQueryLast['column'][] = "$t.headline < ?";
                $arrQueryLast['value'][] = $this->currentProject->headline;
                $arrQueryLast['order'] = "$t.headline ASC";
                break;

            case 'sort_headline_desc':
            case 'order_headline_desc':
                $arrQueryPrev['column'][] = "$t.headline < ?";
                $arrQueryPrev['value'][] = $this->currentProject->headline;
                $arrQueryPrev['order'] = "$t.headline DESC";

                $arrQueryNext['column'][] = "$t.headline > ?";
                $arrQueryNext['value'][] = $this->currentProject->headline;
                $arrQueryNext['order'] = "$t.headline ASC";

                $arrQueryFirst['column'][] = "$t.headline < ?";
                $arrQueryFirst['value'][] = $this->currentProject->headline;
                $arrQueryFirst['order'] = "$t.headline ASC";

                $arrQueryLast['column'][] = "$t.headline > ?";
                $arrQueryLast['value'][] = $this->currentProject->headline;
                $arrQueryLast['order'] = "$t.headline DESC";
                break;

            case 'order_custom_date_asc':
                $arrQueryPrev['column'][] = "$t.sorting < ?";
                $arrQueryPrev['value'][] = $this->currentProject->sorting;
                $arrQueryPrev['order'] = "$t.sorting DESC, $t.date ASC";

                $arrQueryNext['column'][] = "$t.sorting > ?";
                $arrQueryNext['value'][] = $this->currentProject->sorting;
                $arrQueryNext['order'] = "$t.sorting ASC, $t.date ASC";

                $arrQueryFirst['column'][] = "$t.sorting < ?";
                $arrQueryFirst['value'][] = $this->currentProject->sorting;
                $arrQueryFirst['order'] = "$t.sorting ASC, $t.date ASC";

                $arrQueryLast['column'][] = "$t.sorting > ?";
                $arrQueryLast['value'][] = $this->currentProject->sorting;
                $arrQueryLast['order'] = "$t.sorting DESC, $t.date ASC";
                break;

            case 'order_custom_date_desc':
                $arrQueryPrev['column'][] = "$t.sorting < ?";
                $arrQueryPrev['value'][] = $this->currentProject->sorting;
                $arrQueryPrev['order'] = "$t.sorting DESC, $t.date DESC";

                $arrQueryNext['column'][] = "$t.sorting > ?";
                $arrQueryNext['value'][] = $this->currentProject->sorting;
                $arrQueryNext['order'] = "$t.sorting ASC, $t.date DESC";

                $arrQueryFirst['column'][] = "$t.sorting < ?";
                $arrQueryFirst['value'][] = $this->currentProject->sorting;
                $arrQueryFirst['order'] = "$t.sorting ASC, $t.date DESC";

                $arrQueryLast['column'][] = "$t.sorting > ?";
                $arrQueryLast['value'][] = $this->currentProject->sorting;
                $arrQueryLast['order'] = "$t.sorting DESC, $t.date DESC";
                break;

            default:
                $arrQueryPrev['column'][] = "$t.date < ?";
                $arrQueryPrev['value'][] = $this->currentProject->date;
                $arrQueryPrev['order'] = "$t.date DESC";

                $arrQueryNext['column'][] = "$t.date > ?";
                $arrQueryNext['value'][] = $this->currentProject->date;
                $arrQueryNext['order'] = "$t.date ASC";

                $arrQueryFirst['column'][] = "$t.date < ?";
                $arrQueryFirst['value'][] = $this->currentProject->date;
                $arrQueryFirst['order'] = "$t.date ASC";

                $arrQueryLast['column'][] = "$t.date > ?";
                $arrQueryLast['value'][] = $this->currentProject->date;
                $arrQueryLast['order'] = "$t.date DESC";
        }

        $objFirst = ProjectsModel::findAll($arrQueryFirst);
        $objLast = ProjectsModel::findAll($arrQueryLast);

        $objPrev = ProjectsModel::findAll($arrQueryPrev);
        $objNext = ProjectsModel::findAll($arrQueryNext);

        $strFirstLink = $objFirst ? parent::generateProjectUrl($objFirst).($strCategory ? '?category='.$strCategory : '') : null;
        $strLastLink = $objLast ? parent::generateProjectUrl($objLast).($strCategory ? '?category='.$strCategory : '') : null;

        $strPrevLink = $objPrev ? parent::generateProjectUrl($objPrev).($strCategory ? '?category='.$strCategory : '') : null;
        $strNextLink = $objNext ? parent::generateProjectUrl($objNext).($strCategory ? '?category='.$strCategory : '') : null;

        $this->Template->first = $strFirstLink;
        $this->Template->last = $strLastLink;
        $this->Template->prev = $strPrevLink;
        $this->Template->next = $strNextLink;
        $this->Template->firstTitle = $objFirst ? $objFirst->headline : '';
        $this->Template->lastTitle = $objLast ? $objLast->headline : '';
        $this->Template->prevTitle = $objPrev ? $objPrev->headline : '';
        $this->Template->nextTitle = $objNext ? $objNext->headline : '';
        $this->Template->objFirst = $objFirst;
        $this->Template->objLast = $objLast;
        $this->Template->objPrev = $objPrev;
        $this->Template->objNext = $objNext;


        
        // $this->Template->objNext = "fuibu";
    }

    private function isPreviewMode(): bool
    {
        return System::getContainer()->get('contao.security.token_checker')->isPreviewMode();
    }
}
