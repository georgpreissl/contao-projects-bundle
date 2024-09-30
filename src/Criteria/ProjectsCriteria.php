<?php

declare(strict_types=1);

namespace GeorgPreissl\Projects\Criteria;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use GeorgPreissl\Projects\Exception\NoProjectsException;
use GeorgPreissl\GeorgPreissl\Model\ProjectsCategoryModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Database;
use Contao\Date;
use GeorgPreissl\Projects\Model\ProjectsModel;

class ProjectsCriteria
{

    private $columns = [];
    private $values = [];
    private $options = [];

    /**
     * ProjectsCriteria constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    // public function __construct(ContaoFrameworkInterface $framework)
    // {
    //     $this->framework = $framework;
    // }

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TokenChecker $tokenChecker,
    ) {
    }


    /**
     * Set the basic criteria.
     *
     * @param array  $archives
     * @param string $sorting
     *
     * @throws NoProjectException
     */
    public function setBasicCriteria(array $archives, $sorting = null, $featured = null)
    {
        $archives = $this->parseIds($archives);

        if (0 === \count($archives)) {
            throw new NoProjectException();
        }

        $t = ProjectsModel::getTable();

        $this->columns[] = "$t.pid IN(".\implode(',', \array_map('intval', $archives)).')';

        $order = '';

        if ('featured_first' === $featured) {
            $order .= "$t.featured DESC, ";
        }

        // Set the sorting
        switch ($sorting) {
			case 'order_user_asc':
				$order .= "$t.sorting";
				break;
			case 'order_user_desc':
				$order .= "$t.sorting DESC";
				break;            
            case 'order_headline_asc':
                $order .= "$t.headline";
                break;
            case 'order_headline_desc':
                $order .= "$t.headline DESC";
                break;
            case 'order_random':
                $order .= 'RAND()';
                break;
            case 'order_date_asc':
                $order .= "$t.date";
                break;
            default:
                $order .= "$t.date DESC";
                break;
        }

        $this->options['order'] = $order;

        // Never return unpublished elements in the back end, so they don't end up in the RSS feed
        if (!$this->tokenChecker->isPreviewMode()) {
            $time = Date::floorToMinute();
            $this->columns[] = "$t.published=? AND ($t.start=? OR $t.start<=?) AND ($t.stop=? OR $t.stop>?)";
            $this->values = array_merge($this->values, [1, '', $time, '', $time]);
        }
    }

    /**
     * Set the features items.
     *
     * @param bool $enable
     */
    public function setFeatured($enable)
    {
        $t = $this->getProjectsModelAdapter()->getTable();

        if (true === $enable) {
            $this->columns[] = "$t.featured=?";
            $this->values[] = 1;
        } elseif (false === $enable) {
            $this->columns[] = "$t.featured=?";
            $this->values[] = '';
        }
    }

    /**
     * Set the time frame.
     *
     * @param int $begin
     * @param int $end
     */
    public function setTimeFrame($begin, $end)
    {
        $t = $this->getProjectsModelAdapter()->getTable();

        $this->columns[] = "$t.date>=? AND $t.date<=?";
        $this->values[] = $begin;
        $this->values[] = $end;
    }

    /**
     * Set the default categories.
     *
     * @param array       $defaultCategories
     * @param bool        $includeSubcategories
     * @param string|null $order
     *
     * @throws NoProjectException
     */
    public function setDefaultCategories(array $defaultCategories, $includeSubcategories = true, $order = null)
    {
        $defaultCategories = $this->parseIds($defaultCategories);

        if (0 === \count($defaultCategories)) {
            throw new NoProjectException();
        }

        // Include the subcategories
        if ($includeSubcategories) {
            /** @var ProjectsCategoryModel $projectsCategoryModel */
            $projectsCategoryModel = $this->framework->getAdapter(ProjectsCategoryModel::class);
            $defaultCategories = $projectsCategoryModel->getAllSubcategoriesIds($defaultCategories);
        }

        /** @var DcaRelationsModel $model */
        $model = $this->framework->getAdapter(DcaRelationsModel::class);

        $projectIds = $model->getReferenceValues('tl_projects', 'categories', $defaultCategories);
        $projectIds = $this->parseIds($projectIds);

        if (0 === \count($projectIds)) {
            throw new NoProjectException();
        }

        $t = $this->getProjectsModelAdapter()->getTable();

        $this->columns['defaultCategories'] = "$t.id IN(".\implode(',', $projectIds).')';

        // Order project items by best match
        if ($order === 'best_match') {
            $mapper = [];

            // Build the mapper
            foreach (array_unique($projectIds) as $projectId) {
                $mapper[$projectId] = count(array_intersect($defaultCategories, array_unique($model->getRelatedValues($t, 'categories', $projectId))));
            }

            arsort($mapper);

            $this->options['order'] = Database::getInstance()->findInSet("$t.id", array_keys($mapper));
        }
    }

    /**
     * Set the category (intersection filtering).
     *
     * @param int  $category
     * @param bool $preserveDefault
     * @param bool $includeSubcategories
     *
     * @throws NoProjectException
     */
    public function setCategory($category, $preserveDefault = false, $includeSubcategories = false)
    {
        
        /** @var DcaRelationsModel $model */
        $model = $this->framework->getAdapter(DcaRelationsModel::class);

        // Include the subcategories
        if ($includeSubcategories) {
            /** @var ProjectsCategoryModel $projectsCategoryModel */
            $projectsCategoryModel = $this->framework->getAdapter(ProjectsCategoryModel::class);
            $category = $projectsCategoryModel->getAllSubcategoriesIds($category);
        }

        $projectIds = $model->getReferenceValues('tl_projects', 'categories', $category);
        
        $projectIds = $this->parseIds($projectIds);
        
        if (0 === \count($projectIds)) {
            throw new NoProjectException();
        }

        // Do not preserve the default categories
        if (!$preserveDefault) {
            unset($this->columns['defaultCategories']);
        }

        $t = $this->getProjectsModelAdapter()->getTable();

        $this->columns[] = "$t.id IN(".\implode(',', $projectIds).')';
    }

    /**
     * Set the categories (union filtering).
     *
     * @param array $categories
     * @param bool  $preserveDefault
     * @param bool  $includeSubcategories
     *
     * @throws NoProjectException
     */
    public function setCategories($categories, $preserveDefault = false, $includeSubcategories = false)
    {
        
        $allProjectIds = [];

        /** @var DcaRelationsModel $model */
        $model = $this->framework->getAdapter(DcaRelationsModel::class);

        foreach ($categories as $category) {
            // Include the subcategories
            if ($includeSubcategories) {
                /** @var ProjectsCategoryModel $projectsCategoryModel */
                $projectsCategoryModel = $this->framework->getAdapter(ProjectsCategoryModel::class);
                $category = $projectsCategoryModel->getAllSubcategoriesIds($category);
            }

            $projectIds = $model->getReferenceValues('tl_projects', 'categories', $category);
            $projectIds = $this->parseIds($projectIds);

            if (0 === \count($projectIds)) {
                continue;
            }

            $allProjectIds = array_merge($allProjectIds, $projectIds);
        }

        if (\count($allProjectIds) === 0) {
            throw new NoProjectException();
        }

        // Do not preserve the default categories
        if (!$preserveDefault) {
            unset($this->columns['defaultCategories']);
        }

        $t = $this->getProjectsModelAdapter()->getTable();

        $this->columns[] = "$t.id IN(".\implode(',', $allProjectIds).')';
    }

    /**
     * Set the excluded project IDs.
     *
     * @param array $projectIds
     */
    public function setExcludedProject(array $projectIds)
    {
        $projectIds = $this->parseIds($projectIds);

        if (0 === \count($projectIds)) {
            throw new NoProjectException();
        }

        $t = $this->getProjectsModelAdapter()->getTable();

        $this->columns[] = "$t.id NOT IN (".\implode(',', $projectIds).')';
    }

    /**
     * Set the limit.
     *
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->options['limit'] = $limit;
    }

    /**
     * Set the offset.
     *
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->options['offset'] = $offset;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get the project model adapter.
     *
     * @return ProjectsModel
     */
    public function getProjectsModelAdapter()
    {
        /** @var ProjectsModel $adapter */
        $adapter = $this->framework->getAdapter(ProjectsModel::class);

        return $adapter;
    }

    /**
     * Parse the record IDs.
     *
     * @param array $ids
     *
     * @return array
     */
    private function parseIds(array $ids)
    {
        $ids = \array_map('intval', $ids);
        $ids = \array_filter($ids);
        $ids = \array_unique($ids);

        return \array_values($ids);
    }
}
