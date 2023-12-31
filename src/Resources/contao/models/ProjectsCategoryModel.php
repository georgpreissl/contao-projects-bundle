<?php



namespace GeorgPreissl\Projects;

/**
 * Reads and writes project categories
 */
class ProjectsCategoryModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_projects_category';

    /**
     * Get the category URL
     *
     * @param \PageModel $page
     *
     * @return string
     */
    public function getUrl(\PageModel $page)
    {
        $page->loadDetails();

        return $page->getFrontendUrl('/' . ProjectsCategories::getParameterName($page->rootId) . '/' . $this->alias);
    }

    /**
     * Get the target page
     *
     * @return \PageModel|null
     */
    public function getTargetPage()
    {
        $pageId = $this->jumpTo;

        // Inherit the page from parent if there is none set
        if (!$pageId) {
            $pid = $this->pid;

            do {
                $parent = static::findByPk($pid);

                if ($parent !== null) {
                    $pid = $parent->pid;
                    $pageId = $parent->jumpTo;
                }
            } while ($pid && !$pageId);
        }

        return \PageModel::findByPk($pageId);
    }

    /**
     * Find published projects categories by their archives
     *
     * @param array $arrArchives An array of archives
     * @param array $arrIds      An array of categories
     *
     * @return \Model|null The ProjectsModelCategpry or null if there are no categories
     */
    public static function findPublishedByParent($arrArchives, $arrIds=array())
    {
        if (!is_array($arrArchives) || empty($arrArchives)) {
            return null;
        }

        $time = time();
        $t = static::$strTable;
        $arrColumns = array("$t.id IN (SELECT category_id FROM tl_projects_categories WHERE project_id IN (SELECT id FROM tl_projects WHERE pid IN (" . implode(',', array_map('intval', $arrArchives)) . ")" . (!BE_USER_LOGGED_IN ? " AND (tl_projects.start='' OR tl_projects.start<$time) AND (tl_projects.stop='' OR tl_projects.stop>$time) AND tl_projects.published=1" : "") . "))");

        // Filter by custom categories
        if (is_array($arrIds) && !empty($arrIds)) {
            $arrColumns[] = "$t.id IN (" . implode(',', array_map('intval', $arrIds)) . ")";
        }

        if (!BE_USER_LOGGED_IN) {
            $arrColumns[] = "$t.published=1";
        }

        return static::findBy($arrColumns, null, array('order'=>"$t.sorting"));
    }

    /**
     * Find published category by ID or alias
     *
     * @param mixed $varId The numeric ID or alias name
     *
     * @return \Model|null The ProjectCategoryModel or null if there is no category
     */
    public static function findPublishedByIdOrAlias($varId)
    {
        $t = static::$strTable;
        $arrColumns = array("($t.id=? OR $t.alias=?)");

        if (!BE_USER_LOGGED_IN) {
            $arrColumns[] = "$t.published=1";
        }

        return static::findBy($arrColumns, array((is_numeric($varId) ? $varId : 0), $varId));
    }

    /**
     * Find published categories by IDs
     *
     * @param array $arrIds An array of category IDs
     *
     * @return \Model|null The ProjectCategoryModel or null if there is no category
     */
    public static function findPublishedByIds($arrIds)
    {
        if (!is_array($arrIds) || empty($arrIds)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.id IN (" . implode(',', array_map('intval', $arrIds)) . ")");

        if (!BE_USER_LOGGED_IN) {
            $arrColumns[] = "$t.published=1";
        }

        return static::findBy($arrColumns, null, array('order'=>"$t.sorting"));
    }

    /**
     * Find published projects categories by parent ID and IDs
     *
     * @param integer $intPid The parent ID
     * @param array   $arrIds An array of categories
     *
     * @return \Model|null The ProjectsModelCategpry or null if there are no categories
     */
    public static function findPublishedByPidAndIds($intPid, $arrIds)
    {
        if (!is_array($arrIds) || empty($arrIds)) {
            return null;
        }

        $objCategories = \Database::getInstance()->prepare("SELECT c1.*, (SELECT COUNT(*) FROM tl_projects_category c2 WHERE c2.pid=c1.id AND c2.id IN (" . implode(',', array_map('intval', $arrIds)) . ")" . (!BE_USER_LOGGED_IN ? " AND c2.published=1" : "") . ") AS subcategories FROM tl_projects_category c1 WHERE c1.pid=? AND c1.id IN (" . implode(',', array_map('intval', $arrIds)) . ")" . (!BE_USER_LOGGED_IN ? " AND c1.published=1" : "") . " ORDER BY c1.sorting")
                                                 ->execute($intPid);

        if ($objCategories->numRows < 1) {
            return null;
        }

        return \Model\Collection::createFromDbResult($objCategories, static::$strTable);
    }
}
