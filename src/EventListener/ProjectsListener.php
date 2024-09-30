<?php

declare(strict_types=1);


namespace GeorgPreissl\Projects\EventListener;

use GeorgPreissl\Projects\Criteria\ProjectsCriteria;
use GeorgPreissl\Projects\Criteria\ProjectsCriteriaBuilder;
use GeorgPreissl\Projects\Exception\CategoryNotFoundException;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Model\Collection;
use GeorgPreissl\Projects\FrontendModule\ModuleProjectsList;
use GeorgPreissl\Projects\Model\ProjectsModel;

class ProjectsListener
{
    public function __construct(private readonly ProjectsCriteriaBuilder $searchBuilder)
    {
    }

    #[AsHook('projectsListCountItems')]
    public function onProjectsListCountItems(array $archives, bool|null $featured, ModuleProjectsList $module): int
    {
        if (null === ($criteria = $this->getCriteria($archives, $featured, $module))) {
            return 0;
        }

        return ProjectsModel::countBy($criteria->getColumns(), $criteria->getValues());
    }

    /**
     * @return Collection<ProjectsModel>|null
     */
    #[AsHook('projectsListFetchItems')]
    public function onProjectsListFetchItems(array $archives, bool|null $featured, int $limit, int $offset, ModuleProjectsList $module): Collection|null
    {
        if (null === ($criteria = $this->getCriteria($archives, $featured, $module))) {
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

    private function getCriteria(array $archives, bool|null $featured, ModuleProjectsList $module): ProjectsCriteria|null
    {
        try {
            $criteria = $this->searchBuilder->getCriteriaForListModule($archives, $featured, $module);
        } catch (CategoryNotFoundException $e) {
            throw new PageNotFoundException($e->getMessage(), 0, $e);
        }

        return $criteria;
    }
}
