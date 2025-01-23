<?php


namespace GeorgPreissl\Projects;

use Contao\PageModel;
use Contao\Config;
use Contao\StringUtil;
use Contao\Environment;
use Contao\Frontend;
use Contao\ArticleModel;
use Contao\System;
use Contao\UserModel;
use GeorgPreissl\Projects\Model\ProjectsModel;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class Projects extends Frontend
{


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

		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use the content URL generator instead.', __METHOD__);

		try
		{
			$parameters = array();

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && Input::get('month'))
			{
				$parameters['month'] = Input::get('month');
			}

			$url = System::getContainer()->get('contao.routing.content_url_generator')->generate($objItem, $parameters, $blnAbsolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
		}
		catch (ExceptionInterface)
		{
			return StringUtil::ampersand(Environment::get('requestUri'));
		}

		return $url;


	}







	/**
	 * Return the schema.org data from a project
	 *
	 * @param ProjectsModel $objProject
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(ProjectsModel $objProject): array
	{
		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');
		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

		$jsonLd = array(
			'@type' => 'Project',
			'identifier' => '#/schema/project/' . $objProject->id,
			'headline' => $htmlDecoder->inputEncodedToPlainText($objProject->headline),
			'datePublished' => date('Y-m-d\TH:i:sP', $objProject->date),
		);

		try
		{
			$jsonLd['url'] = $urlGenerator->generate($objProject);
		}
		catch (ExceptionInterface)
		{
			// noop
		}

		if ($objProject->location)
		{
			$jsonLd['location'] = $htmlDecoder->inputEncodedToPlainText($objProject->location);
		}

		if ($objProject->teaser)
		{
			$jsonLd['description'] = $htmlDecoder->htmlToPlainText($objProject->teaser);
		}

		if ($objAuthor = UserModel::findById($objProject->author))
		{
			$jsonLd['author'] = array(
				'@type' => 'Person',
				'name' => $objAuthor->name,
			);
		}

		return $jsonLd;
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
