<?php


namespace GeorgPreissl\Projects;

/**
 * Provide methods regarding project categories
 */
class ProjectsCategories
{

    /**
     * Get the parameter name
     *
     * @param int $rootId
     *
     * @return string
     */
    public static function getParameterName($rootId = null)
    {
        if (!$rootId) {
            $rootId = $GLOBALS['objPage']->rootId;
        }

        $rootPage = \PageModel::findByPk($rootId);

        if ($rootPage === null) {
            return '';
        }

        return $rootPage->projectCategories_param ?: 'category';
    }

    /**
     * Translate the URL parameters
     *
     * @param array  $params
     * @param string $language
     * @param array  $rootPage
     *
     * @return array
     */
    public function translateUrlParameters(array $params, $language, array $rootPage)
    {
        $currentParam = static::getParameterName();
        $newParam = static::getParameterName($rootPage['id']);

        if (isset($params['url'][$currentParam]) && $currentParam != $newParam) {
            $params['url'][$newParam] = $params['url'][$currentParam];
            unset($params['url'][$currentParam]);
        }

        return $params;
    }

    /**
     * Check if the system is multilingual installed
     * @return boolean
     */
    public static function checkMultilingual()
    {
        
        return (in_array('dc_multilingual', \Config::getInstance()->getActiveModules()) && count(static::getAvailableLanguages()) > 1) ? true : false;
    }

    /**
     * Return a list of available languages
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return \Database::getInstance()->execute("SELECT DISTINCT(language) FROM tl_page WHERE type='root'")->fetchEach('language');
    }

    /**
     * Get a fallback language
     * @return string
     */
    public static function getFallbackLanguage()
    {
        return \Database::getInstance()->execute("SELECT language FROM tl_page WHERE type='root' AND fallback=1")->language;
    }

    /**
     * Get the model class name
     * @return string
     */
    public static function getModelClass()
    {
        return static::checkMultilingual() ? 'ProjectsCategoryMultilingualModel' : 'ProjectsCategoryModel';
    }
}
