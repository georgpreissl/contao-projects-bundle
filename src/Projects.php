<?php


namespace GeorgPreissl\Projects;

use Contao\PageModel;
use Contao\Config;
use Contao\StringUtil;
use Contao\Environment;
use Contao\Frontend;
use Contao\ArticleModel;


class Projects extends Frontend
{
	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();    

	/**
	 * Page cache array
	 * @var array
	 */
	private static $arrPageCache = array();

	/**
	 * Add project items to the indexer
	 *
	 * @param array   $arrPages
	 * @param integer $intRoot
	 * @param boolean $blnIsSitemap
	 *
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$arrProcessed = array();
		$time = time();

		// Get all project archives
		$objArchive = ProjectsArchiveModel::findByProtected('');

		// Walk through each archive
		if ($objArchive !== null)
		{
			while ($objArchive->next())
			{
				// Skip project archives without target page
				if (!$objArchive->jumpTo)
				{
					continue;
				}

				// Skip project archives outside the root nodes
				if (!empty($arrRoot) && !in_array($objArchive->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objArchive->jumpTo]))
				{
					$objParent = \PageModel::findByPk($objArchive->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start != '' && $objParent->start > $time) || ($objParent->stop != '' && $objParent->stop <= ($time + 60)))
					{
						continue;
					}

					// The target page is exempt from the sitemap (see #6418)
					if ($blnIsSitemap && $objParent->sitemap == 'map_never')
					{
						continue;
					}

					// Generate the URL
					$arrProcessed[$objArchive->jumpTo] = $objParent->getAbsoluteUrl((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objArchive->jumpTo];
                
				// Get the items
				$objArticle = ProjectsModel::findPublishedDefaultByPid($objArchive->id);

				if ($objArticle !== null)
				{
					while ($objArticle->next())
					{
						$arrPages[] = $this->getLink($objArticle, $strUrl);
					}
				}
			}
		}

		return $arrPages;
	}




	/**
	 * Generate a URL and return it as string
	 *
	 * @param ProjectsModel $objItem
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateProjectUrl($objItem, $blnAddArchive=false, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objItem->id . ($blnAbsolute ? '_absolute' : '') . (($blnAddArchive && Input::get('month')) ? '_' . Input::get('month') : '');

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				if (0 === strncmp($objItem->url, 'mailto:', 7))
				{
					self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objItem->url);
				}
				else
				{
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($objItem->url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = ArticleModel::findByPk($objItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);

					/** @var PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('request'));
			}
			else
			{
				$params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->alias ?: $objItem->id);

				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && Input::get('month'))
			{
				self::$arrUrlCache[$strCacheKey] .= '?month=' . Input::get('month');
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}


















	/**
	 * Return the link of a project article
	 *
	 * @param \ProjectModel $objItem
	 * @param string     $strUrl
	 * @param string     $strBase
	 *
	 * @return string
	 */
	protected function getLink($objItem, $strUrl, $strBase='')
	{
		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				return $objItem->url;
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) !== null)
				{
					/** @var \PageModel $objTarget */
					return $strBase . $objTarget->getFrontendUrl();
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = \ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null && ($objPid = $objArticle->getRelated('pid')) !== null)
				{
					/** @var \PageModel $objPid */
					return $strBase . ampersand($objPid->getFrontendUrl('/articles/' . ((!\Config::get('disableAlias') && $objArticle->alias != '') ? $objArticle->alias : $objArticle->id)));
				}
				break;
		}

		// Link to the default page
		return $strBase . sprintf($strUrl, (($objItem->alias != '' && !\Config::get('disableAlias')) ? $objItem->alias : $objItem->id));
	}





    /**
     * Add the project categories to the project template
     * @param object
     * @param array
     */
    public function addProjectCategoriesToTemplate($objTemplate, $arrData)
    {
        // wird in contao/config/config.php verwendet:
        // $GLOBALS['TL_HOOKS']['parseArticles'][] = array('GeorgPreissl\Projects\Projects', 'addProjectCategoriesToTemplate');
		

    }

    /**
     * Parse project categories insert tags
     *
     * @param string $tag
     *
     * @return string|bool
     */
    public function parseCategoriesTags($tag)
    {
        // not in Use!!!!
        
        $chunks = trimsplit('::', $tag);

        if ($chunks[0] === 'project_categories') {
            // $className = \ProjectCategories\ProjectCategories::getModelClass();
            $param     = ProjectsCategories::getParameterName();

            if (($projectModel = ProjectsCategoryModel::findPublishedByIdOrAlias(\Input::get($param))) !== null) {
                return $projectModel->{$chunks[1]};
            }
        }

        return false;
    }

    /**
     * Generate an XML files and save them to the root directory
     * @param array
     */
    protected function generateFiles($arrFeed)
    {
        $arrArchives = deserialize($arrFeed['archives']);

        if (!is_array($arrArchives) || empty($arrArchives))
        {
            return;
        }

        $strType = ($arrFeed['format'] == 'atom') ? 'generateAtom' : 'generateRss';
        $strLink = $arrFeed['feedBase'] ?: \Environment::get('base');
        $strFile = $arrFeed['feedName'];

        $objFeed = new \Feed($strFile);
        $objFeed->link = $strLink;
        $objFeed->title = $arrFeed['title'];
        $objFeed->description = $arrFeed['description'];
        $objFeed->language = $arrFeed['language'];
        $objFeed->published = $arrFeed['tstamp'];

        $arrCategories = deserialize($arrFeed['categories']);

        // Filter by categories
        if (is_array($arrCategories) && !empty($arrCategories)) {
            $GLOBALS['PROJECT_FILTER_CATEGORIES'] = true;
            $GLOBALS['PROJECT_FILTER_DEFAULT'] = $arrCategories;
        } else {
            $GLOBALS['PROJECT_FILTER_CATEGORIES'] = false;
        }

        // Get the items
        if ($arrFeed['maxItems'] > 0)
        {
            // printf('<pre>%s</pre>', print_r($arrArchives,true));
            $objArticle = \GeorgPreissl\Projects\ProjectsModel::findPublishedByPids($arrArchives, null, $arrFeed['maxItems']);
        }
        else
        {
            $objArticle = \GeorgPreissl\Projects\ProjectsModel::findPublishedByPids($arrArchives);
        }

        // Parse the items
        if ($objArticle !== null)
        {
            $arrUrls = array();

            while ($objArticle->next())
            {
                $jumpTo = $objArticle->getRelated('pid')->jumpTo;

                // No jumpTo page set (see #4784)
                if (!$jumpTo)
                {
                    continue;
                }

                // Get the jumpTo URL
                if (!isset($arrUrls[$jumpTo]))
                {
                    $objParent = \PageModel::findWithDetails($jumpTo);

                    // A jumpTo page is set but does no longer exist (see #5781)
                    if ($objParent === null)
                    {
                        $arrUrls[$jumpTo] = false;
                    }
                    else
                    {
                        $arrUrls[$jumpTo] = $objParent->getAbsoluteUrl((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s');
                    }
                }

                // Skip the event if it requires a jumpTo URL but there is none
                if ($arrUrls[$jumpTo] === false && $objArticle->source == 'default')
                {
                    continue;
                }

                // Get the categories
                if ($arrFeed['categories_show']) {
                    $arrCategories = array();

                    if (($objCategories = ProjectsCategoryModel::findPublishedByIds(deserialize($objArticle->categories, true))) !== null) {
                        $arrCategories = $objCategories->fetchEach('title');
                    }
                }

                $strUrl = $arrUrls[$jumpTo];
                $objItem = new \FeedItem();

                // Add the categories to the title
                if ($arrFeed['categories_show'] == 'title') {
                    $objItem->title = sprintf('[%s] %s', implode(', ', $arrCategories), $objArticle->headline);
                } else {
                    $objItem->title = $objArticle->headline;
                }

                $objItem->link = $this->getLink($objArticle, $strUrl);
                $objItem->published = $objArticle->date;
                $objItem->author = $objArticle->authorName;

                // Prepare the description
                if ($arrFeed['source'] == 'source_text')
                {
                    $strDescription = '';
                    $objElement = \ContentModel::findPublishedByPidAndTable($objArticle->id, 'tl_project');

                    if ($objElement !== null)
                    {
                        // Overwrite the request (see #7756)
                        $strRequest = \Environment::get('request');
                        \Environment::set('request', $objItem->link);

                        while ($objElement->next())
                        {
                            $strDescription .= $this->getContentElement($objElement->current());
                        }

                        \Environment::set('request', $strRequest);
                    }
                }
                else
                {
                    $strDescription = $objArticle->teaser;
                }

                // Add the categories to the description
                if ($arrFeed['categories_show'] == 'text_before' || $arrFeed['categories_show'] == 'text_after') {
                    $strCategories = '<p>' . $GLOBALS['TL_LANG']['MSC']['projectCategories'] . ' ' .  implode(', ', $arrCategories) . '</p>';

                    if ($arrFeed['categories_show'] == 'text_before') {
                        $strDescription = $strCategories . $strDescription;
                    } else {
                        $strDescription .= $strCategories;
                    }
                }

                $strDescription = $this->replaceInsertTags($strDescription, false);
                $objItem->description = $this->convertRelativeUrls($strDescription, $strLink);

                // Add the article image as enclosure
                if ($objArticle->addImage)
                {
                    $objFile = \FilesModel::findByUuid($objArticle->singleSRC);

                    if ($objFile !== null)
                    {
                        $objItem->addEnclosure($objFile->path, $strLink);
                    }
                }

                // Enclosures
                if ($objArticle->addEnclosure)
                {
                    $arrEnclosure = deserialize($objArticle->enclosure, true);

                    if (is_array($arrEnclosure))
                    {
                        $objFile = \FilesModel::findMultipleByUuids($arrEnclosure);

                        if ($objFile !== null)
                        {
                            while ($objFile->next())
                            {
                                $objItem->addEnclosure($objFile->path, $strLink);
                            }
                        }
                    }
                }

                $objFeed->addItem($objItem);
            }
        }

        // Create the file
        \File::putContent('share/' . $strFile . '.xml', $this->replaceInsertTags($objFeed->$strType(), false));
    }





}
