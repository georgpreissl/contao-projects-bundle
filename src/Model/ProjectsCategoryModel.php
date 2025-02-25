<?php



namespace GeorgPreissl\Projects\Model;

use Codefog\HasteBundle\DcaRelationsManager;
use Codefog\HasteBundle\Model\DcaRelationsModel;
use GeorgPreissl\Projects\MultilingualHelper;
use Contao\Database;
use Contao\Date;
use Contao\FilesModel;
use Contao\Model\Collection;
use GeorgPreissl\Projects\Model\ProjectsModel;
use Contao\System;
use Contao\Model;










class ProjectsCategoryModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_projects_category';

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        // Fix the compatibility with DC_Multilingual v4 (#184)
        if ($name === 'id' && MultilingualHelper::isActive() && $this->lid) {
            return $this->lid;
        }

        return parent::__get($name);
    }




    public function getAlias(): string
    {
        return $this->alias;
    }





    /**
     * Get the CSS class.
     *
     * @return string
     */
    public function getCssClass()
    {
        $cssClasses = [
            'projects_category_'.$this->id,
            'category_'.$this->id,
        ];

        if ($this->cssClass) {
            $cssClasses[] = $this->cssClass;
        }

        return \implode(' ', \array_unique($cssClasses));
    }

    /**
     * Get the image.
     *
     * @return FilesModel|null
     */
    public function getImage()
    {
        return $this->image ? FilesModel::findByPk($this->image) : null;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->frontendTitle ?: $this->title;
    }

    /**
     * Find published projects categories by projects criteria.
     *
     * @param array $archives
     * @param array $ids
     * @param array $aliases
     * @param array $excludedIds
     *
     * @return Collection|null
     */
    public static function findPublishedByArchives(array $archives, array $ids = [], array $aliases = [], array $excludedIds = [], array $arrOptions = []): Collection|null
    {
        if (0 === \count($archives)) {
            return null;
        }

        /** @var DcaRelationsManager $dcaRelationsManager */
        $dcaRelationsManager = System::getContainer()->get(DcaRelationsManager::class);

        if (null === ($relation = $dcaRelationsManager->getRelation('tl_projects', 'categories'))) {
            return null;
        }

        $t = static::getTableAlias();
        $values = [];

        // Start sub select query for relations
        $subSelect = "SELECT {$relation['related_field']} 
FROM {$relation['table']} 
WHERE {$relation['reference_field']} IN (SELECT id FROM tl_projects WHERE pid IN (".\implode(',', \array_map('intval', $archives)).')';

        // Include only the published projects items
        if (!self::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $subSelect .= ' AND (start=? OR start<=?) AND (stop=? OR stop>?) AND published=?';
            $values = \array_merge($values, ['', $time, '', $time + 60, 1]);
        }



        // Finish sub select query for relations
        $subSelect .= ')';

        // Columns definition start
        $columns = ["$t.id IN ($subSelect)"];

        // Filter by custom categories
        if (\count($ids) > 0) {
            $columns[] = "$t.id IN (".\implode(',', \array_map('intval', $ids)).')';
        }

        // Filter by excluded IDs
        if (\count($excludedIds) > 0) {
            $columns[] = "$t.id NOT IN (".\implode(',', \array_map('intval', $excludedIds)).')';
        }

        // Filter by custom aliases
        if (\count($aliases) > 0) {
            if (MultilingualHelper::isActive()) {
                $columns[] = "($t.alias IN ('".\implode("','", $aliases)."') OR translation.alias IN ('".\implode("','", $aliases)."'))";
            } else {
                $columns[] = "$t.alias IN ('".\implode("','", $aliases)."')";
            }
        }

        if (!self::isPreviewMode($arrOptions)) {
            $columns[] = "$t.published=?";
            $values[] = 1;
        }

        return static::findBy($columns, $values, ['order' => "$t.sorting"]);
    }

    /**
     * Find published category by ID or alias.
     *
     * @param string $idOrAlias
     *
     * @return ProjectsCategoryModel|null
     */
    // public static function findPublishedByIdOrAlias($idOrAlias)
    public static function findPublishedByIdOrAlias(string $idOrAlias, array $arrOptions = []): self|null
    {
        $values = [];
        $columns = [];
        $t = static::getTableAlias();

        // Determine the alias condition
        if (is_numeric($idOrAlias)) {
            $columns[] = "$t.id=?";
            $values[] = (int) $idOrAlias;
        } else {
            if (MultilingualHelper::isActive()) {
                $columns[] = "($t.alias=? OR translation.alias=?)";
                $values[] = $idOrAlias;
                $values[] = $idOrAlias;
            } else {
                $columns[] = "$t.alias=?";
                $values[] = $idOrAlias;
            }
        }

        if (!self::isPreviewMode($arrOptions)) {
            $columns[] = "$t.published=?";
            $values[] = 1;
        }
        return static::findOneBy($columns, $values);
    }


    /**
     * Find published projects categories.
     *
     * @return Collection<static>|null
     */
    public static function findPublished(array $arrOptions = []): Collection|null
    {
        $t = static::getTable();
        $arrOptions = array_merge(['order' => "$t.sorting"], $arrOptions);

        if (self::isPreviewMode($arrOptions)) {
            return static::findAll($arrOptions);
        }

        return static::findBy('published', 1, $arrOptions);
    }












    /**
     * Find published projects categories by parent ID and IDs.
     *
     * @param array    $ids
     * @param int|null $pid
     *
     * @return Collection|null
     */
    public static function findPublishedByIds(array $ids, int|null $pid = null, array $arrOptions = []): Collection|null
    {
        if (0 === \count($ids)) {
            return null;
        }

        $t = static::getTableAlias();
        $columns = ["$t.id IN (".\implode(',', \array_map('intval', $ids)).')'];
        $values = [];

        // Filter by pid
        if (null !== $pid) {
            $columns[] = "$t.pid=?";
            $values[] = $pid;
        }

        if (!self::isPreviewMode($arrOptions)) {
            $columns[] = "$t.published=?";
            $values[] = 1;
        }
        return static::findBy($columns, $values, ['order' => "$t.sorting"]);
    }

    /**
     * Find published projects categories by parent ID.
     *
     * @param int $pid
     *
     * @return Collection|null
     */
    public static function findPublishedByPid($pid)
    {
        $t = static::getTableAlias();
        $columns = ["$t.pid=?"];
        $values = [$pid];

        if (!BE_USER_LOGGED_IN) {
            $columns[] = "$t.published=?";
            $values[] = 1;
        }

        return static::findBy($columns, $values, ['order' => "$t.sorting"]);
    }



    /**
     * Find the published categories by project.
     *
     * @return Collection<static>|array|null
     */
    public static function findPublishedByProject(array|int $projectId, array $arrOptions = []): Collection|array|null
    {
        if (0 === \count($ids = DcaRelationsModel::getRelatedValues('tl_projects', 'categories', $projectId))) {
            return null;
        }

        $t = static::getTable();
        $columns = ["$t.id IN (".implode(',', array_map('intval', array_unique($ids))).')'];
        $values = [];

        if (!self::isPreviewMode($arrOptions)) {
            $columns[] = "$t.published=?";
            $values[] = 1;
        }

        return static::findBy($columns, $values, array_merge(['order' => "$t.sorting"], $arrOptions));
    }











    /**
     * Count the published projects by archives.
     *
     * @param array    $archives
     * @param int|null $category
     * @param bool     $includeSubcategories
     * @param array    $cumulativeCategories
     * @param bool     $unionFiltering
     *
     * @return int
     */
    public static function getUsage(array $archives = [], $category = null, $includeSubcategories = false, array $cumulativeCategories = [], $unionFiltering = false)
    {
        $t = ProjectsModel::getTable();

        // Include the subcategories
        if (null !== $category && $includeSubcategories) {
            $category = static::getAllSubcategoriesIds($category);
        }

        $ids = DcaRelationsModel::getReferenceValues($t, 'categories', $category);
        $ids = array_map('intval', $ids);

        // Also filter by cumulative categories
        if (count($cumulativeCategories) > 0) {
            $cumulativeIds = null;

            foreach ($cumulativeCategories as $cumulativeCategory) {
                // Include the subcategories
                if ($includeSubcategories) {
                    $cumulativeCategory = static::getAllSubcategoriesIds($cumulativeCategory);
                }

                $projectsIds = DcaRelationsModel::getReferenceValues($t, 'categories', $cumulativeCategory);
                $projectsIds = array_map('intval', $projectsIds);

                if ($cumulativeIds === null) {
                    $cumulativeIds = $projectsIds;
                } else {
                    $cumulativeIds = $unionFiltering ? array_merge($cumulativeIds, $projectsIds) : array_intersect($cumulativeIds, $projectsIds);
                }
            }

            $ids = $unionFiltering ? array_merge($ids, $cumulativeIds) : array_intersect($ids, $cumulativeIds);
        }

        if (0 === \count($ids)) {
            return 0;
        }

        $columns = ["$t.id IN (".\implode(',', \array_unique($ids)).')'];
        $values = [];

        // Filter by archives
        if (\count($archives)) {
            $columns[] = "$t.pid IN (".\implode(',', \array_map('intval', $archives)).')';
        }
        $hasBackendUser = System::getContainer()->get('contao.security.token_checker')->hasBackendUser();

        if (!$hasBackendUser) {
            $time = Date::floorToMinute();
            $columns[] = "$t.published=? AND ($t.start=? OR $t.start<=?) AND ($t.stop=? OR $t.stop>?)";
            $values = \array_merge($values, [1, '', $time, '', $time]);
        }

        return ProjectsModel::countBy($columns, $values);
    }




    /**
     * Get all subcategory IDs.
     */
    public static function getAllSubcategoriesIds(array|int|string $category): array
    {
        $ids = Database::getInstance()->getChildRecords($category, static::$strTable, false, (array) $category, !self::isPreviewMode([]) ? 'published=1' : '');

        return array_map('intval', $ids);
    }


    /**
     * {@inheritdoc}
     */
    public static function findMultipleByIds($arrIds, array $arrOptions = [])
    {
        if (!MultilingualHelper::isActive()) {
            return parent::findMultipleByIds($arrIds, $arrOptions);
        }

        $t = static::getTableAlias();

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = Database::getInstance()->findInSet("$t.id", $arrIds);
        }

        return static::findBy(["$t.id IN (".\implode(',', \array_map('intval', $arrIds)).')'], null);
    }

    /**
     * Get the table alias.
     *
     * @return string
     */
    public static function getTableAlias()
    {
        return static::$strTable;
    }
}
