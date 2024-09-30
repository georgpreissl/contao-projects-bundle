<?php

declare(strict_types=1);

namespace GeorgPreissl\Projects;

use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\FilesModel;
use Contao\Module;
use Contao\ModuleNewsArchive;
use Contao\ModuleNewsList;
use Contao\ModuleNewsReader;
use Contao\PageModel;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Service\ResetInterface;




#[Autoconfigure(public: true)]
class ProjectsCategoriesManager implements ResetInterface
{
    private array $urlCache = [];

    private array $trailCache = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string $projectDir,
    ) {
    }





    /**
     * Generate the category URL.
     *
     * @param ProjectsCategoryModel $category
     * @param PageModel         $page
     * @param bool              $absolute
     *
     * @return string
     */
    // public function generateUrl(ProjectsCategoryModel $category, PageModel $page, $absolute = false)
    // {
        
    //     $page->loadDetails();
    //     $cacheKey = $page->id . '-' . ($absolute ? 'abs' : 'rel');

    //     if (!isset($this->urlCache[$cacheKey])) {
    //         $params = '/%s/%s';
    //         $this->urlCache[$cacheKey] = $absolute ? $page->getAbsoluteUrl($params) : $page->getFrontendUrl($params);
    //     }

    //     return sprintf($this->urlCache[$cacheKey], $this->getParameterName($page->rootId), $this->getCategoryAlias($category, $page));
    // }

    /**
     * Generate the category URL.
     */
    public function generateUrl(ProjectsCategoryModel $category, PageModel $page, bool $absolute = false): string
    {
        $cacheKey = $page->id.'-'.$category->id.'-'.($absolute ? 'abs' : 'rel');

        if (isset($this->urlCache[$cacheKey])) {
            return $this->urlCache[$cacheKey];
        }

        $page->loadDetails();
        $params = \sprintf('/%s/%s', $this->getParameterName($page->rootId), $category->getAlias($page->language));
        $this->urlCache[$cacheKey] = $absolute ? $page->getAbsoluteUrl($params) : $page->getFrontendUrl($params);

        return $this->urlCache[$cacheKey];
    }


    /**
     * Get the image.
     *
     * @param ProjectsCategoryModel $category
     *
     * @return \Contao\FilesModel|null
     */
    public function getImage(ProjectsCategoryModel $category)
    {
        if (null === ($image = $category->getImage()) || !\is_file(TL_ROOT.'/'.$image->path)) {
            return null;
        }

        return $image;
    }

    /**
     * Get the category alias
     *
     * @param ProjectsCategoryModel $category
     * @param PageModel         $page
     *
     * @return string
     */
    public function getCategoryAlias(ProjectsCategoryModel $category, PageModel $page)
    {
        if ($category instanceof Multilingual) {
            return $category->getAlias($page->language);
        }

        return $category->alias;
    }


    /**
     * Get the parameter name.
     */
    public function getParameterName(int|null $rootId = null): string
    {
        $rootId = $rootId ?: $GLOBALS['objPage']->rootId;

        if (!$rootId || null === ($rootPage = PageModel::findById($rootId))) {
            return 'category';
        }
        
        return $rootPage->projectCategories_param ?: 'category';
    }






    /**
     * Get the category target page.
     *
     * @param ProjectsCategoryModel $category
     *
     * @return PageModel|null
     */
    public function getTargetPage(ProjectsCategoryModel $category)
    {
        $pageId = $category->jumpTo;

        // Inherit the page from parent if there is none set
        if (!$pageId) {
            $pid = $category->pid;

            do {
                /** @var ProjectsCategoryModel $parent */
                $parent = $category->findByPk($pid);

                if (null !== $parent) {
                    $pid = $parent->pid;
                    $pageId = $parent->jumpTo;
                }
            } while ($pid && !$pageId);
        }

        // Get the page model
        if ($pageId) {
            static $pageCache = [];

            if (!isset($pageCache[$pageId])) {
                /** @var PageModel $pageAdapter */
                $pageAdapter = $this->framework->getAdapter(PageModel::class);
                $pageCache[$pageId] = $pageAdapter->findPublishedById($pageId);
            }

            return $pageCache[$pageId];
        }

        return null;
    }

    /**
     * Get the category trail IDs.
     *
     * @param ProjectsCategoryModel $category
     *
     * @return array
     */
    public function getTrailIds(ProjectsCategoryModel $category)
    {
        static $cache;

        if (!isset($cache[$category->id])) {
            /** @var Database $db */
            $db = $this->framework->createInstance(Database::class);

            $ids = $db->getParentRecords($category->id, $category->getTable());
            $ids = \array_map('intval', \array_unique($ids));

            // Remove the current category
            unset($ids[\array_search($category->id, $ids, true)]);

            $cache[$category->id] = $ids;
        }

        return $cache[$category->id];
    }

    /**
     * Return true if the category is visible for module.
     *
     * @param ProjectsCategoryModel $category
     * @param Module            $module
     *
     * @return bool
     */
    public function isVisibleForModule(ProjectsCategoryModel $category, Module $module)
    {
        // List or archive module
        if ($category->hideInList && ($module instanceof ModuleProjectsList || $module instanceof ModuleProjectsArchive)) {
            return false;
        }

        // Reader module
        if ($category->hideInReader && $module instanceof ModuleProjectsReader) {
            return false;
        }

        return true;
    }
    public function reset(): void
    {
        $this->urlCache = [];
        $this->trailCache = [];
    }


}
