<?php



namespace GeorgPreissl\Projects;

/**
 * Front end module "project categories".
 */
class ModuleProjectsCategories extends ModuleProjects
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_projectscategories';

    /**
     * Active category
     * @var object
     */
    protected $objActiveCategory = null;

    /**
     * Active project categories
     * @var array
     */
    protected $active = array();

    /**
     * Category trail
     * @var array
     */
    protected $arrCategoryTrail = array();

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### PROJECT CATEGORIES MENU ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        $this->projects_archives = $this->sortOutProtected(deserialize($this->projects_archives));
        // dump($this->projects_archives);

        // Return if there are no archives
        if (!is_array($this->projects_archives) || empty($this->projects_archives)) {
            return '';
        }

        $param = 'items';

        // Use the auto_item parameter if enabled
        if (!isset($_GET['items']) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item'])) {
            $param = 'auto_item';
        }

        $projectsModel = ProjectsModel::findPublishedByParentAndIdOrAlias(\Input::get($param), $this->projects_archives);

        $this->activeProjectsCategories = array();

        // Get the category IDs of the active project item
        if ($projectsModel !== null) {
            $this->activeProjectsCategories = deserialize($projectsModel->categories, true);
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        // $strClass = \ProjectCategories\ProjectCategories::getModelClass();
        // string(21) "\ProjectCategoryModel" 

        $objCategories = ProjectsCategoryModel::findPublishedByParent($this->projects_archives, ($this->projects_customCategories ? deserialize($this->projects_categories) : null));

        // Return if no categories are found
        if ($objCategories === null) {
            $this->Template->categories = '';

            return;
        }

        global $objPage;
        
        $strParam = ProjectsCategories::getParameterName();
        // string(8) "category" 

        $strUrl = $this->generateFrontendUrl($objPage->row(), '/' . $strParam . '/%s');
        // string(22) "referenzen/category/%s"

        // Get the jumpTo page
        // means: If as special jumpTo page is defined in the modul "project_categories"
        if ($this->jumpTo > 0 && $objPage->id != $this->jumpTo) {
            $objJump = \PageModel::findByPk($this->jumpTo);

            if ($objJump !== null) {
                $strUrl = $this->generateFrontendUrl($objJump->row(), '/' . $strParam . '/%s');
            }
        }

        $arrIds = array();

        // Get the parent categories IDs
        while ($objCategories->next()) {
            $arrIds = array_merge($arrIds, $this->Database->getParentRecords($objCategories->id, 'tl_projects_category'));
            
        }
        //$arrIds e.g.:
        // Array
        // (
        //     [0] => 5
        //     [1] => 1
        //     [2] => 2
        // )        


        // Get the active category
        if (\Input::get($strParam) != '') {

            $strCategory = \Input::get($strParam);
            // e.g. string(7) "hochbau" 

            $this->objActiveCategory = ProjectsCategoryModel::findPublishedByIdOrAlias($strCategory);

            if ($this->objActiveCategory !== null) {
                $this->arrCategoryTrail = $this->Database->getParentRecords($this->objActiveCategory->id, 'tl_projects_category');

                // Remove the current category from the trail
                unset($this->arrCategoryTrail[array_search($this->objActiveCategory->id, $this->arrCategoryTrail)]);
            }
        }

        $rootId = 0;

        // Set the custom root ID
        // not in use
        if ($this->projects_categoriesRoot) {
            $rootId = $this->projects_categoriesRoot;
            
        }

        $this->Template->categories = $this->renderProjectsCategories($rootId, array_unique($arrIds), $strUrl);
    }

    /**
     * Recursively compile the projects categories and return it as HTML string
     * @param integer
     * @param integer
     * @return string
     */
    protected function renderProjectsCategories($intPid, $arrIds, $strUrl, $intLevel=1)
    {
        // $strClass = \ProjectsCategories\ProjectsCategories::getModelClass();
        $objCategories = ProjectsCategoryModel::findPublishedByPidAndIds($intPid, $arrIds);

        if ($objCategories === null) {
            return '';
        }

        $strParam = ProjectsCategories::getParameterName();
        $arrCategories = array();

        // Layout template fallback
        if ($this->navigationTpl == '') {
            $this->navigationTpl = 'nav_projectscategories';
        }

        $objTemplate = new \FrontendTemplate($this->navigationTpl);
        $objTemplate->type = get_class($this);
        $objTemplate->cssID = $this->cssID;
        $objTemplate->level = 'level_' . $intLevel;
        $objTemplate->showQuantity = $this->projects_showQuantity;

        $count = 0;
        $total = $objCategories->count();

        // Add the "reset categories" link
        if ($this->projects_resetCategories && $intLevel == 1) {
            $intProjectsQuantity = 0;

            // Get the project quantity
            if ($this->projects_showQuantity) {
                $intProjectsQuantity = ProjectsModel::countPublishedByCategoryAndPids($this->projects_archives);
            }

            $blnActive = \Input::get($strParam) ? false : true;

            $arrCategories[] = array
            (
                'isActive' => $blnActive,
                'subitems' => '',
                'class' => 'reset first' . (($total == 1) ? ' last' : '') . ' even' . ($blnActive ? ' active' : ''),
                'title' => specialchars($GLOBALS['TL_LANG']['MSC']['resetCategories'][1]),
                'linkTitle' => specialchars($GLOBALS['TL_LANG']['MSC']['resetCategories'][1]),
                'link' => $GLOBALS['TL_LANG']['MSC']['resetCategories'][0],
                'href' => ampersand(str_replace('/' . $strParam . '/%s', '', $strUrl)),
                'quantity' => $intProjectQuantity
            );

            $count = 1;
            $total++;
        }

        $intLevel++;

        // Render categories
        while ($objCategories->next()) {
            $strSubcategories = '';

            // Get the subcategories
            if ($objCategories->subcategories) {
                $strSubcategories = $this->renderProjectsCategories($objCategories->id, $arrIds, $strUrl, $intLevel);
            }

            $blnActive = ($this->objActiveCategory !== null) && ($this->objActiveCategory->id == $objCategories->id);
            $strClass = ('projects_category_' . $objCategories->id) . ($objCategories->cssClass ? (' ' . $objCategories->cssClass) : '') . ((++$count == 1) ? ' first' : '') . (($count == $total) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even') . ($blnActive ? ' active' : '') . (($strSubcategories != '') ? ' submenu' : '') . (in_array($objCategories->id, $this->arrCategoryTrail) ? ' trail' : '') . (in_array($objCategories->id, $this->activeProjectsCategories) ? ' projects_trail' : '');
            $strTitle = $objCategories->frontendTitle ?: $objCategories->title;

            $arrRow = $objCategories->row();
            $arrRow['isActive'] = $blnActive;
            $arrRow['subitems'] = $strSubcategories;
            $arrRow['class'] = $strClass;
            $arrRow['title'] = specialchars($strTitle, true);
            $arrRow['linkTitle'] = specialchars($strTitle, true);
            $arrRow['link'] = $strTitle;
            $arrRow['href'] = ampersand(sprintf($strUrl, ($GLOBALS['TL_CONFIG']['disableAlias'] ? $objCategories->id : $objCategories->alias)));
            $arrRow['quantity'] = 0;

            // Get the projects quantity
            if ($this->projects_showQuantity) {
                $arrRow['quantity'] = ProjectsModel::countPublishedByCategoryAndPids($this->projects_archives, $objCategories->id);
            }

            $arrCategories[] = $arrRow;
        }

        $objTemplate->items = $arrCategories;

        return !empty($arrCategories) ? $objTemplate->parse() : '';
    }
}
