<?php


namespace GeorgPreissl\Projects\FrontendModule;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use GeorgPreissl\Projects\Criteria\ProjectsCriteria;
use GeorgPreissl\Projects\Exception\NoProjectsException;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\ProjectsCategoriesManager;
use GeorgPreissl\Projects\FrontendModule\ModuleProjects;
use GeorgPreissl\Projects\Model\ProjectsModel;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Database;
use Contao\Input;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

abstract class ModuleProjectsCategories extends ModuleProjects
{
    /**
     * Active category.
     *
     * @var ProjectsCategoryModel
     */
    protected $activeCategory = null;

    /**
     * Active categories.
     *
     * @var Collection|null
     */
    protected $activeCategories;

    /**
     * Projects categories of the current project item.
     *
     * @var array
     */
    protected $currentProjectsCategories = [];

    /**
     * @var ProjectsCategoriesManager
     */
    protected $manager;

    /**
     * @var PageModel|null
     */
    protected $targetPage;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $template->parse();
        }

        $this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives, true));

        // Return if there are no archives
        if (0 === \count($this->projects_archives)) {
            return '';
        }

        // $this->manager = System::getContainer()->get('georgpreissl_project_categories.manager');
        $this->manager = System::getContainer()->get(ProjectsCategoriesManager::class);
        $this->currentProjectsCategories = $this->getCurrentProjectsCategories();
        // dump( $this->currentProjectsCategories );
        return parent::generate();
    }

    /**
     * Get the URL category separator character.
     *
     * @return string
     */
    public static function getCategorySeparator()
    {
        return '__';
    }

    /**
     * Get the categories.
     *
     * @return Collection|null
     */
    protected function getCategories()
    {
        
        // Check, if the selection of categories has been restricted in the module settings
        $customCategories = $this->projects_customCategories ? StringUtil::deserialize($this->projects_categories, true) : [];

        // Get the subcategories of custom categories
        if (\count($customCategories) > 0) {
            $customCategories = ProjectsCategoryModel::getAllSubcategoriesIds($customCategories);
        }

        // Get all categories whether they have projects or not
        if ($this->projects_showEmptyCategories) {
            if (\count($customCategories) > 0) {
                $categories = ProjectsCategoryModel::findPublishedByIds($customCategories);
            } else {
                $categories = ProjectsCategoryModel::findPublished();
            }
        } else {
            // Get the categories that do have projects assigned
            $categories = ProjectsCategoryModel::findPublishedByArchives($this->projects_archives, $customCategories);
        }

        return $categories;
    }

    /**
     * Get the active categories.
     *
     * @param array $customCategories
     *
     * @return Collection|null
     */
    protected function getActiveCategories(array $customCategories = [])
    {
        // $param = System::getContainer()->get('georgpreissl_project_categories.manager')->getParameterName();
        $param = System::getContainer()->get(ProjectsCategoriesManager::class)->getParameterName();


        if (!($aliases = Input::get($param))) {
            return null;
        }

        $aliases = StringUtil::trimsplit(static::getCategorySeparator(), $aliases);
        $aliases = \array_unique(\array_filter($aliases));

        if (0 === \count($aliases)) {
            return null;
        }

        // Get the categories that do have projects assigned
        $models = ProjectsCategoryModel::findPublishedByArchives($this->projects_archives, $customCategories, $aliases);

        // No models have been found but there are some aliases present
        if (null === $models && 0 !== \count($aliases)) {
            Controller::redirect($this->getTargetPage()->getFrontendUrl());
        }

        // Validate the provided aliases with the categories found
        if (null !== $models) {
            $realAliases = [];

            /** @var ProjectsCategoryModel $model */
            foreach ($models as $model) {
                $realAliases[] = $this->manager->getCategoryAlias($model, $GLOBALS['objPage']);
            }

            if (\count(\array_diff($aliases, $realAliases)) > 0) {
                Controller::redirect($this->getTargetPage()->getFrontendUrl(\sprintf(
                    '/%s/%s',
                    $this->manager->getParameterName($GLOBALS['objPage']->rootId),
                    \implode(static::getCategorySeparator(), $realAliases)
                )));
            }
        }

        return $models;
    }

    /**
     * Get the inactive categories.
     *
     * @param array $customCategories
     *
     * @return Collection|null
     */
    protected function getInactiveCategories(array $customCategories = [])
    {
        $excludedIds = [];

        // Find only the categories that still can display some results combined with active categories
        if (null !== $this->activeCategories) {
            // Union filtering
            if ($this->projects_filterCategoriesUnion) {
                $excludedIds = $this->activeCategories->fetchEach('id');
            } else {
                // Intersection filtering
                $columns = [];
                $values = [];

                // Collect the projects that match all active categories
                /** @var ProjectsCategoryModel $activeCategory */
                foreach ($this->activeCategories as $activeCategory) {
                    $criteria = new ProjectsCriteria(System::getContainer()->get('contao.framework'));

                    try {
                        $criteria->setBasicCriteria($this->projects_archives);
                        $criteria->setCategory($activeCategory->id, false, (bool) $this->projects_includeSubcategories);
                    } catch (NoProjectsException $e) {
                        continue;
                    }

                    $columns = \array_merge($columns, $criteria->getColumns());
                    $values = \array_merge($values, $criteria->getValues());
                }

                // Should not happen but you never know
                if (0 === \count($columns)) {
                    return null;
                }

                $projectsIds = Database::getInstance()
                    ->prepare('SELECT id FROM tl_projects WHERE '.\implode(' AND ', $columns))
                    ->execute($values)
                    ->fetchEach('id')
                ;

                if (0 === \count($projectsIds)) {
                    return null;
                }

                $categoryIds = DcaRelationsModel::getRelatedValues('tl_projects', 'categories', $projectsIds);
                $categoryIds = \array_map('intval', $categoryIds);
                $categoryIds = \array_unique(\array_filter($categoryIds));

                // Include the parent categories
                if ($this->projects_includeSubcategories) {
                    foreach ($categoryIds as $categoryId) {
                        $categoryIds = \array_merge($categoryIds, \array_map('intval', Database::getInstance()->getParentRecords($categoryId, 'tl_projects_category')));
                    }
                }

                // Remove the active categories, so they are not considered again
                $categoryIds = \array_diff($categoryIds, $this->activeCategories->fetchEach('id'));

                // Filter by custom categories
                if (\count($customCategories) > 0) {
                    $categoryIds = \array_intersect($categoryIds, $customCategories);
                }

                $categoryIds = \array_values(\array_unique($categoryIds));

                if (0 === \count($categoryIds)) {
                    return null;
                }

                $customCategories = $categoryIds;
            }
        }

        return ProjectsCategoryModel::findPublishedByArchives($this->projects_archives, $customCategories, [], $excludedIds);
    }

    /**
     * Get the target page, the page containing the projects overview.
     *
     * @return PageModel
     */
    protected function getTargetPage()
    {
    
        if (null === $this->targetPage) {
            if ($this->jumpTo > 0
                && (int) $GLOBALS['objPage']->id !== (int) $this->jumpTo
                && null !== ($target = PageModel::findPublishedById($this->jumpTo))
            ) {
                $this->targetPage = $target;
            } else {
                $this->targetPage = $GLOBALS['objPage'];
            }
        }
        // dump($this->targetPage);
        return $this->targetPage;
    }

    /**
     * Get the category IDs of the current project item.
     *
     * @return array
     */
    protected function getCurrentProjectsCategories()
    {
        if (!($alias = Input::get('auto_item', false, true))
            || null === ($projects = ProjectsModel::findPublishedByParentAndIdOrAlias($alias, $this->projects_archives))
        ) {
            return [];
        }

        $ids = DcaRelationsModel::getRelatedValues('tl_projects', 'categories', $projects->id);
        $ids = \array_map('intval', \array_unique($ids));

        return $ids;
    }

    /**
     * Generate the item.
     *
     * @param string                 $url
     * @param string                 $link
     * @param string                 $title
     * @param string                 $cssClass
     * @param bool                   $isActive
     * @param string                 $subitems
     * @param ProjectsCategoryModel|null $category
     *
     * @return array
     */
    protected function generateItem($url, $link, $title, $cssClass, $isActive, $subitems = '', ProjectsCategoryModel $category = null)
    {
        $data = [];

        // Set the data from category
        if (null !== $category) {
            $data = $category->row();
        }

        $data['isActive'] = $isActive;
        $data['subitems'] = $subitems;
        $data['class'] = $cssClass;
        $data['title'] = StringUtil::specialchars($title);
        $data['linkTitle'] = StringUtil::specialchars($title);
        $data['link'] = $link;
        $data['href'] = StringUtil::ampersand($url);
        $data['quantity'] = 0;

        // Add the "active" class
        if ($isActive) {
            $data['class'] = \trim($data['class'].' active');
        }

        // Add the "submenu" class
        if ($subitems) {
            $data['class'] = \trim($data['class'].' submenu');
        }

        // Add the projects quantity
        if ($this->projects_showQuantity) {
            if (null === $category) {
                $data['quantity'] = ProjectsCategoryModel::getUsage($this->projects_archives, null, false, [], (bool) $this->projects_filterCategoriesUnion);
            } else {
                $data['quantity'] = ProjectsCategoryModel::getUsage(
                    $this->projects_archives,
                    $category->id,
                    (bool) $this->projects_includeSubcategories,
                    (null !== $this->activeCategories) ? $this->activeCategories->fetchEach('id') : [],
                    (bool) $this->projects_filterCategoriesUnion
                );
            }
        }

        // Add the image
        if (null !== $category && null !== ($image = $this->manager->getImage($category))) {
            $data['image'] = new \stdClass();
            Controller::addImageToTemplate($data['image'], [
                'singleSRC' => $image->path,
                'size' => $this->projects_categoryImgSize,
                'alt' => $title,
                'imageTitle' => $title,
            ]);
        } else {
            $data['image'] = null;
        }

        return $data;
    }

    /**
     * Generate the item CSS class.
     *
     * @param ProjectsCategoryModel $category
     *
     * @return string
     */
    protected function generateItemCssClass(ProjectsCategoryModel $category)
    {
        $cssClasses = [$category->getCssClass()];

        // Add the trail class
        if (\in_array((int) $category->id, $this->manager->getTrailIds($category), true)) {
            $cssClasses[] = 'trail';
        } elseif (null !== $this->activeCategory && \in_array((int) $category->id, $this->manager->getTrailIds($this->activeCategory), true)) {
            $cssClasses[] = 'trail';
        }

        // Add the projects trail class
        if (\in_array((int) $category->id, $this->currentProjectsCategories, true)) {
            $cssClasses[] = 'projects_trail';
        }

        return \implode(' ', $cssClasses);
    }
}
