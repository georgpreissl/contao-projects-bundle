<?php



namespace GeorgPreissl\Projects\FrontendModule;

use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\ProjectsCategoriesManager;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;

/**
 * Front end module "project categories".
 */
class ModuleProjectsCategoriesMenu extends ModuleProjectsCategories
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_projectscategories';

    /**
     * Generate the module.
     */
    protected function compile()
    {
        
        $categories = $this->getCategories();

        // Return if no categories are found
        if (null === $categories) {
            $this->Template->categories = '';

            return;
        }

        $container = System::getContainer();
        $param = $container->get(ProjectsCategoriesManager::class)->getParameterName();


        // Get the active category
        if (($alias = Input::get($param)) && null !== ($activeCategory = ProjectsCategoryModel::findPublishedByIdOrAlias($alias))) {
            $this->activeCategory = $activeCategory;

            // Set the canonical URL
            if ($this->projects_enableCanonicalUrls && ($responseContext = $container->get('contao.routing.response_context_accessor')->getResponseContext())) {
                /** @var ResponseContext $responseContext */
                if ($responseContext->has(HtmlHeadBag::class)) {
                    /** @var HtmlHeadBag $htmlHeadBag */
                    $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
                    $htmlHeadBag->setCanonicalUri($GLOBALS['objPage']->getAbsoluteUrl());
                    
                }
            }
        }

        $ids = [];

        // Get the parent categories IDs
        /** @var ProjectsCategoryModel $category */
        foreach ($categories as $category) {
            $ids = \array_merge($ids, Database::getInstance()->getParentRecords($category->id, $category->getTable()));
        }

        $this->Template->categories = $this->renderProjectsCategories((int) $this->projects_categoriesRoot, \array_unique($ids));
    }

    /**
     * Recursively compile the projects categories and return it as HTML string.
     *
     * @param int   $pid
     * @param array $ids
     * @param int   $level
     *
     * @return string
     */
    protected function renderProjectsCategories($pid, array $ids, $level = 1)
    {
        if (null === ($categories = ProjectsCategoryModel::findPublishedByIds($ids, $pid))) {
            return '';
        }

        // Layout template fallback
        if (!$this->navigationTpl) {
            $this->navigationTpl = 'nav_projectscategories_list';
        }

        $template = new FrontendTemplate($this->navigationTpl);
        $template->type = \get_class($this);
        $template->cssID = $this->cssID;
        $template->level = 'level_'.$level;
        $template->showQuantity = $this->projects_showQuantity;

        $items = [];

        // Add the "reset categories" link
        if ($this->projects_resetCategories && 1 === $level) {
            $items[] = $this->generateItem(
                $this->getTargetPage()->getFrontendUrl(),
                $GLOBALS['TL_LANG']['MSC']['resetCategories'][0],
                $GLOBALS['TL_LANG']['MSC']['resetCategories'][1],
                'reset',
                0 === \count($this->currentProjectsCategories) && null === $this->activeCategory
            );
        }

        ++$level;

        /** @var ProjectsCategoryModel $category */
        foreach ($categories as $category) {
            // Generate the category individual URL or the filter-link
            if ($this->projects_forceCategoryUrl && null !== ($targetPage = $this->manager->getTargetPage($category))) {
                $url = $targetPage->getFrontendUrl();
            } else {

                $url = $this->manager->generateUrl($category, $this->getTargetPage());
                
            }


            $items[] = $this->generateItem(
                $url,
                $category->getTitle(),
                $category->getTitle(),
                $this->generateItemCssClass($category),
                null !== $this->activeCategory && (int) $this->activeCategory->id === (int) $category->id,
                (!$this->showLevel || $this->showLevel >= $level) ? $this->renderProjectsCategories($category->id, $ids, $level) : '',
                $category
            );
        }

        $template->items = $items;

        return $template->parse();
    }
}
