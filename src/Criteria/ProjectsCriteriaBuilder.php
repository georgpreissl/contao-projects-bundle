<?php



namespace GeorgPreissl\Projects\Criteria;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use GeorgPreissl\Projects\Exception\CategoryNotFoundException;
use GeorgPreissl\Projects\Exception\NoProjectException;
use GeorgPreissl\Projects\FrontendModule\CumulativeFilterModule;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\ProjectsCategoriesManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class ProjectsCriteriaBuilder
{



    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TokenChecker $tokenChecker,
        private readonly Connection $db,
        private readonly ProjectsCategoriesManager $manager,
    ) {
    }

    public function create(array $archives): ProjectsCriteria
    {
        $criteria = new ProjectsCriteria($this->framework, $this->tokenChecker);
        $criteria->setBasicCriteria($archives);

        return $criteria;
    }



    /**
     * Get the criteria for archive module.
     *
     * @param array  $archives
     * @param int    $begin
     * @param int    $end
     * @param Module $module
     *
     * @return ProjectsCriteria|null
     */
    public function getCriteriaForArchiveModule(array $archives, $begin, $end, Module $module)
    {
        $criteria = new ProjectsCriteria($this->framework);

        try {
            $criteria->setBasicCriteria($archives, $module->projects_order);

            // Set the time frame
            $criteria->setTimeFrame($begin, $end);

            // Set the regular list criteria
            $this->setRegularListCriteria($criteria, $module);
        } catch (NoProjectException $e) {
            return null;
        }

        return $criteria;
    }

    /**
     * Get the criteria for list module.
     *
     * @param array     $archives
     * @param bool|null $featured
     * @param Module    $module
     *
     * @return ProjectsCriteria|null
     */
    public function getCriteriaForListModule(array $archives, $featured, Module $module)
    {
        $criteria = new ProjectsCriteria($this->framework, $this->tokenChecker);
        
        /*
        $criteria sieht jetzt so aus:
        GeorgPreissl\Projects\Criteria\ProjectsCriteria {#2602 ▼
        -columns: []
        -values: []
        -options: []
        ...
        }
        */


        try {
            $criteria->setBasicCriteria($archives, $module->projects_order, $module->projects_featured);
            
            /*
            $criteria sieht jetzt so aus:
            GeorgPreissl\Projects\Criteria\ProjectsCriteria {#2602 ▼
                -columns: array:2 [▼
                    0 => "tl_projects.pid IN(1)"
                    1 => "tl_projects.published=? AND (tl_projects.start=? OR tl_projects.start<=?) AND (tl_projects.stop=? OR tl_projects.stop>?)"
                ]
                -values: array:5 [▼
                    0 => 1
                    1 => ""
                    2 => 1737636420
                    3 => ""
                    4 => 1737636420
                ]
                -options: array:1 [▼
                    "order" => "tl_projects.sorting"
                ]
            }
            */
            // Set the featured filter
            if (null !== $featured) {
                $criteria->setFeatured($featured);
            }


			if($module->projects_relatedOnly){

				$projectAlias = Input::get('auto_item', false, true);
				
				if (!empty($projectAlias)) {
		
					$project = ProjectsModel::findByAlias($projectAlias);

					if (null !== $project) {
						$arrRelatedIds = array_map('intval', StringUtil::deserialize($project->relatedProjects, true));


                        $criteria->setRelatedProjects($arrRelatedIds);
						// dump($criteria);
						
					}
					

				}				

						
			}



            // Set the criteria for related categories
            if ($module->projects_relatedCategories) {
                $this->setRelatedListCriteria($criteria, $module);
            } else {
                // Wenn es sich um ein 'normales' Filtermenü handelt:
                // Set the regular list criteria
                $this->setRegularListCriteria($criteria, $module);
            }
            /*
            $criteria sieht jetzt, nach dem setRegularListCriteria() bzw. das darin enthaltene setCategory() angewendet wurde, so aus:
            GeorgPreissl\Projects\Criteria\ProjectsCriteria {#2521 ▼
            -columns: array:3 [▼
                0 => "tl_projects.pid IN(1)"
                1 => "tl_projects.published=? AND (tl_projects.start=? OR tl_projects.start<=?) AND (tl_projects.stop=? OR tl_projects.stop>?)"
                2 => "tl_projects.id IN(8,14)"
            ]
            -values: array:5 [▼
                0 => 1
                1 => ""
                2 => 1737636660
                3 => ""
                4 => 1737636660
            ]
            -options: array:1 [▼
                "order" => "tl_projects.sorting"
            ]
            }            
            */
            
        } catch (NoProjectException $e) {
            return null;
        }
        
        return $criteria;
    }

    /**
     * Get the criteria for menu module.
     *
     * @param array  $archives
     * @param Module $module
     *
     * @return ProjectsCriteria|null
     */
    public function getCriteriaForMenuModule(array $archives, Module $module)
    {
        $criteria = new ProjectsCriteria($this->framework);

        try {
            $criteria->setBasicCriteria($archives, $module->projects_order);

            // Set the regular list criteria
            $this->setRegularListCriteria($criteria, $module);
        } catch (NoProjectException $e) {
            return null;
        }

        return $criteria;
    }

    /**
     * Set the regular list criteria.
     *
     * @param ProjectsCriteria $criteria
     * @param Module       $module
     *
     * @throws CategoryNotFoundException
     * @throws NoProjectException
     */
    private function setRegularListCriteria(ProjectsCriteria $criteria, Module $module)
    {
        
        // Filter by default categories
        if (\count($default = StringUtil::deserialize($module->projects_filterDefault, true)) > 0) {
            $criteria->setDefaultCategories($default);
        }

        // Filter by multiple active categories
        if ($module->projects_filterCategoriesCumulative) {
            
            /** @var Input $input */
            $input = $this->framework->getAdapter(Input::class);
            $param = $this->manager->getParameterName();

            if ($aliases = $input->get($param)) {
                $aliases = StringUtil::trimsplit(CumulativeFilterModule::getCategorySeparator(), $aliases);
                $aliases = array_unique(array_filter($aliases));

                if (count($aliases) > 0) {
                    /** @var ProjectsCategoryModel $model */
                    $model = $this->framework->getAdapter(ProjectsCategoryModel::class);
                    $categories = [];

                    foreach ($aliases as $alias) {
                        // Return null if the category does not exist
                        if (null === ($category = $model->findPublishedByIdOrAlias($alias))) {
                            throw new CategoryNotFoundException(sprintf('Project category "%s" was not found', $alias));
                        }

                        $categories[] = (int) $category->id;
                    }

                    if (count($categories) > 0) {
                        // Union filtering
                        if ($module->projects_filterCategoriesUnion) {
                            $criteria->setCategories($categories, (bool) $module->projects_filterPreserve, (bool) $module->projects_includeSubcategories);
                        } else {
                            // Intersection filtering
                            foreach ($categories as $category) {
                                $criteria->setCategory($category, (bool) $module->projects_filterPreserve, (bool) $module->projects_includeSubcategories);
                            }
                        }
                    }
                }
            }

            return;
        }

        // Filter by active category
        if ($module->projects_filterCategories) {
            /** @var Input $input */
            $input = $this->framework->getAdapter(Input::class);
            
            $param = $this->manager->getParameterName();

            if ($alias = $input->get($param)) {
                // $alias ist zb. 'tuerme' wenn die Kategorie 'Türme' ausgewählt wurde
                
                /** @var ProjectsCategoryModel $model */
                $model = $this->framework->getAdapter(ProjectsCategoryModel::class);
                
                // Return null if the category does not exist
                if (null === ($category = $model->findPublishedByIdOrAlias($alias))) {
                    throw new CategoryNotFoundException(sprintf('Project category "%s" was not found', $alias));
                }
                
                // hier wird nun bspw. "tl_projects.id IN(8,14)" zu '$criteria->columns' hinzugefügt:
                $criteria->setCategory($category->id, (bool) $module->projects_filterPreserve, (bool) $module->projects_includeSubcategories);
                
            }
        }
    }

    /**
     * Set the related list criteria.
     *
     * @param ProjectsCriteria $criteria
     * @param Module       $module
     *
     * @throws NoProjectException
     */
    private function setRelatedListCriteria(ProjectsCriteria $criteria, Module $module)
    {
        if (null === ($project = $module->currentProject)) {
            throw new NoProjectException();
        }

        /** @var DcaRelationsModel $adapter */
        $adapter = $this->framework->getAdapter(DcaRelationsModel::class);
        $categories = \array_unique($adapter->getRelatedValues($project->getTable(), 'categories', $project->id));

        // This project has no project categories assigned
        if (0 === \count($categories)) {
            throw new NoProjectException();
        }

        $categories = \array_map('intval', $categories);
        $excluded = $this->db->fetchFirstColumn('SELECT id FROM tl_projects_category WHERE excludeInRelated=1');

        // Exclude the categories
        foreach ($excluded as $category) {
            if (false !== ($index = \array_search((int) $category, $categories, true))) {
                unset($categories[$index]);
            }
        }

        // Exclude categories by root
        if ($module->projects_categoriesRoot > 0) {
            $categories = array_intersect($categories, ProjectsCategoryModel::getAllSubcategoriesIds($module->projects_categoriesRoot));
        }

        // There are no categories left
        if (0 === \count($categories)) {
            throw new NoProjectException();
        }

        $criteria->setDefaultCategories($categories, (bool) $module->projects_includeSubcategories, $module->projects_relatedCategoriesOrder);
        $criteria->setExcludedProject([$project->id]);
    }
}
