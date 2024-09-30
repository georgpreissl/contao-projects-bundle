<?php



namespace GeorgPreissl\Projects\FrontendModule;

use Contao\ArrayUtil;
use Contao\StringUtil;
use Contao\System;
use Contao\Module;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\FilesModel;
use Contao\ContentModel;
use Contao\CommentsModel;
use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;

abstract class ModuleProjects extends Module
{


	private static $arrUrlCache = array();


	protected function sortOutProtected($arrArchives)
	{
		if (empty($arrArchives) || !\is_array($arrArchives))
		{
			return $arrArchives;
		}

		$objArchive = ProjectsArchiveModel::findMultipleByIds($arrArchives);
		$arrArchives = array();

		if ($objArchive !== null)
		{
			$security = System::getContainer()->get('security.helper');

			while ($objArchive->next())
			{
				if ($objArchive->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objArchive->groups))
				{
					continue;
				}

				$arrArchives[] = $objArchive->id;
			}
		}

		return $arrArchives;
	}

	/**
	 * Parse an item and return it as string
	 *
	 * @param \ProjectsModel $objProject
	 * @param boolean    $blnAddArchive
	 * @param string     $strClass
	 * @param integer    $intCount
	 *
	 * @return string
	 */
	protected function parseProject($objProject, $blnAddArchive=false, $strClass='', $intCount=0)
	{
		/** @var \PageModel $objPage */
		global $objPage;

		/** @var \FrontendTemplate|object $objTemplate */
		$objTemplate = new FrontendTemplate($this->projects_template);
		$objTemplate->setData($objProject->row());

		// parse the gallery
		$this->multiSRC = StringUtil::deserialize($objProject->multiSRC);

		// Get the file entries from the database
		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		// dump($this->multiSRC);
		// dump($this->objFiles);

		$images = array();
		$test = array();
		$auxDate = array();
		$objFiles = $this->objFiles;

		if ($this->objFiles != null)
		{

			// Get all images
			while ($objFiles->next())
			{

				// Continue if the files has been processed or does not exist
				if (isset($images[$objFiles->path]) || !file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path))
				{
					continue;
				}

				// Check whether the user has selected files or a folder
				if ($objFiles->type == 'file')
				{
					$objFile = new File($objFiles->path);

					if (!$objFile->isImage)
					{
						continue;
					}

					// Add the image
					$images[$objFiles->path] = array
					(
						'id'         => $objFiles->id,
						'uuid'       => $objFiles->uuid,
						'name'       => $objFile->basename,
						'singleSRC'  => $objFiles->path,
						'filesModel' => $objFiles->current()
					);

					$auxDate[] = $objFile->mtime;


					
				}

				// Folders
				else
				{
					$objSubfiles = FilesModel::findByPid($objFiles->uuid, array('order' => 'name'));

					if ($objSubfiles === null)
					{
						continue;
					}

					while ($objSubfiles->next())
					{
						// Skip subfolders
						if ($objSubfiles->type == 'folder')
						{
							continue;
						}

						$objFile = new File($objSubfiles->path);
						// dump($objSubfiles->meta);
						if (!$objFile->isImage)
						{
							continue;
						}

						// Add the image
						$images[$objSubfiles->path] = array
						(
							'id'         => $objSubfiles->id,
							'uuid'       => $objSubfiles->uuid,
							'name'       => $objFile->basename,
							'singleSRC'  => $objSubfiles->path,
							'filesModel' => $objSubfiles->current()
						);

						$auxDate[] = $objFile->mtime;
					}
				}



			}

		}

		// Sort array
		switch ($objProject->sortBy)
		{
			default:
			case 'name_asc':
				uksort($images, static function ($a, $b): int
				{
					return strnatcasecmp(basename($a), basename($b));
				});
				break;

			case 'name_desc':
				uksort($images, static function ($a, $b): int
				{
					return -strnatcasecmp(basename($a), basename($b));
				});
				break;

			case 'date_asc':
				uasort($images, static function (array $a, array $b)
				{
					return $a['mtime'] <=> $b['mtime'];
				});
				break;

			case 'date_desc':
				uasort($images, static function (array $a, array $b)
				{
					return $b['mtime'] <=> $a['mtime'];
				});
				break;

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			case 'meta':
				trigger_deprecation('contao/core-bundle', '4.0', 'The "meta" key in "Contao\ContentGallery::compile()" has been deprecated and will no longer work in Contao 5.0.');
				// no break

			case 'custom':
				$images = ArrayUtil::sortByOrderField($images, $objProject->orderSRC);
				break;

			case 'random':
				shuffle($images);
				$this->Template->isRandomOrder = true;
				break;
		}

		$images = array_values($images);


		$i = 0;
		$strLightboxId = 'lb' . $objProject->id;

		foreach ($images as $pic) {
			$objPic = new \stdClass();
			// $pic['size'] = 8;
			$pic['size'] = [560, 360, 'crop'];
			// whether a lightbox should be used
			$pic['fullsize'] = true;
			// dump($pic);
			$this->addImageToTemplate($objPic, $pic, null, $strLightboxId);
			$test[$i] = $objPic;
			$i++;
		}

		// dump($test);


		$objTemplate->images = $images;
		$objTemplate->test = $test;






		// printf('<pre>%s</pre>', print_r($objProject,true));

		$objTemplate->class = (($objProject->cssClass != '') ? ' ' . $objProject->cssClass : '') . $strClass;
		$objTemplate->projectHeadline = $objProject->headline;
		$objTemplate->subHeadline = $objProject->subheadline;
		$objTemplate->hasSubHeadline = $objProject->subheadline ? true : false;
		$objTemplate->hasDescription = $objProject->description ? true : false;
		$objTemplate->hasCompany = $objProject->company ? true : false;
		$objTemplate->hasTrades = $objProject->trades ? true : false;
		$objTemplate->hasLocation = $objProject->location ? true : false;
		$objTemplate->hasTotalArea = $objProject->totalArea ? true : false;
		$objTemplate->hasOrderValue = $objProject->orderValue ? true : false;
		$objTemplate->hasShare = $objProject->share ? true : false;
		$objTemplate->linkHeadline = $this->generateLink($objProject->headline, $objProject, $blnAddArchive);
		$objTemplate->more = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objProject, $blnAddArchive, true);
		$objTemplate->link = Projects::generateProjectUrl($objProject, $blnAddArchive);
		// $objTemplate->archive = $objProject->getRelated('pid');
		$objTemplate->count = $intCount; // see #5708
		$objTemplate->text = '';
		$objTemplate->hasText = true;
		$objTemplate->hasTeaser = false;

		// Clean the RTE output
		if ($objProject->teaser != '')
		{
			$objTemplate->hasTeaser = true;

			if ($objPage->outputFormat == 'xhtml')
			{
				$objTemplate->teaser = StringUtil::toXhtml($objProject->teaser);
			}
			else
			{
				$objTemplate->teaser = StringUtil::toHtml5($objProject->teaser);
			}

			$objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
		}

$objProject->source = 'default';

		// Display the "read more" button for external/article links
		if ($objProject->source != 'default')
		{
			$objTemplate->text = true;
			$objTemplate->hasText = true;
			// var_dump("jo");
		}

		// Compile the project text
		else
		{
			$id = $objProject->id;

			$objTemplate->text = function () use ($id)
			{
				$strText = '';
				$objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_projects');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strText .= $this->getContentElement($objElement->current());
					}
				}

				return $strText;
			};

			$objTemplate->hasText = (ContentModel::countPublishedByPidAndTable($objProject->id, 'tl_projects') > 0);
		}

		$arrMeta = $this->getMetaFields($objProject);

		// Add the meta information
		// $objTemplate->date = $arrMeta['date'];
		$objTemplate->hasMetaFields = !empty($arrMeta);
		// $objTemplate->numberOfComments = $arrMeta['ccount'];
		// $objTemplate->commentCount = $arrMeta['comments'];
		$objTemplate->timestamp = $objProject->date;
		// $objTemplate->author = $arrMeta['author'];
		$objTemplate->datetime = date('Y-m-d\TH:i:sP', $objProject->date);

		$objTemplate->addImage = false;

		// Add an image
		if ($objProject->addImage)
		{
			$imgSize = $objProject->size ?: null;

			// Override the default image size
			if ($this->imgSize)
			{
				$size = StringUtil::deserialize($this->imgSize);

				if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
				{
					$imgSize = $this->imgSize;
				}
			}

			$figureBuilder = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($objProject->singleSRC)
				->setSize($imgSize)
				->setOverwriteMetadata($objProject->getOverwriteMetadata())
				->enableLightbox((bool) $objProject->fullsize);

			// If the external link is opened in a new window, open the image link in a new window as well (see #210)
			if ('external' === $objTemplate->source && $objTemplate->target)
			{
				$figureBuilder->setLinkAttribute('target', '_blank');
			}

			if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
			{
				// Rebuild with link to the news article if we are not in a
				// newsreader and there is no link yet. $intCount will only be
				// set by the news list and news archive modules (see #5851).
				if ($intCount > 0 && !$figure->getLinkHref())
				{
					$linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objProject->headline), true);

					$figure = $figureBuilder
						->setLinkHref($objTemplate->link)
						->setLinkAttribute('title', $linkTitle)
						->build();
				}

				$figure->applyLegacyTemplateData($objTemplate, $objProject->imagemargin, $objProject->floating);
			}
		}

		$objTemplate->enclosure = array();

		// Add enclosures
		if ($objProject->addEnclosure)
		{
			$this->addEnclosuresToTemplate($objTemplate, $objProject->row());
		}



$arrData = $objProject->row();
		// $arrData enthält alle Infos zu einem Projekt
		// dump($arrData);
        // diese Funktion wir für jede aufgelistete Referenz einmal ausgeführt!

		// dump($arrData['categories']);
		// dump($objProject->categories);

        if (isset($arrData['categories'])) {

            // $arrData['categories']
            // e.g.: string(18) "a:1:{i:0;s:1:"1";}"


            $arrCategories = array();
            $arrCategoriesList = array();

            $categories = StringUtil::deserialize($arrData['categories']);
            // e.g.: Array
            // (
            //     [0] => 5
            //     [1] => 2
            // )
            // d.h. dieses Projekt ist in den Kategorien mit der ID 5 und 2

            if (is_array($categories) && !empty($categories)) {

                // $strClass = \GeorgPreissl\ProjectsCategories\ProjectsCategories::getModelClass();
                // string(21) "\ProjectCategoryModel" 
                
                $objCategories = ProjectsCategoryModel::findPublishedByIds($categories);
				// dump($objCategories);
                
                // Add the categories to template
                if ($objCategories !== null) {
                    /** @var ProjectCategoryModel $objCategory */
                    foreach ($objCategories as $objCategory) {

                        $strName = $objCategory->frontendTitle ? $objCategory->frontendTitle : $objCategory->title;
                        // e.g.: string(7) "Holzbau" 

                        $arrCategories[$objCategory->id] = $objCategory->row();
                        $arrCategories[$objCategory->id]['name'] = $strName;
                        $arrCategories[$objCategory->id]['class'] = 'category_' . $objCategory->id . ($objCategory->cssClass ? (' ' . $objCategory->cssClass) : '');
                        $arrCategories[$objCategory->id]['linkTitle'] = StringUtil::specialchars($strName);
                        $arrCategories[$objCategory->id]['href'] = '';
                        $arrCategories[$objCategory->id]['hrefWithParam'] = '';
                        $arrCategories[$objCategory->id]['targetPage'] = null;

                        // Add the target page
                        // if (($targetPage = $objCategory->getTargetPage()) !== null) {
                        //     $arrCategories[$objCategory->id]['href'] = $targetPage->getFrontendUrl();
                        //     $arrCategories[$objCategory->id]['hrefWithParam'] = $targetPage->getFrontendUrl('/' . ProjectsCategories::getParameterName() . '/' . $objCategory->alias);
                        //     $arrCategories[$objCategory->id]['targetPage'] = $targetPage;
                        // }

                        // Register a function to generate category URL manually
                        $arrCategories[$objCategory->id]['getUrl'] = function(PageModel $page) use ($objCategory) {
                            return $objCategory->getUrl($page);
                        };

                        // Generate categories list
                        $arrCategoriesList[$objCategory->id] = $strName;
                    }

                    // Sort the category list alphabetically
                    asort($arrCategoriesList);

                    // Sort the categories alphabetically
                    uasort($arrCategories, function($a, $b) {
                        return strnatcasecmp($a['name'], $b['name']);
                    });
                }
            }

			// dump($objTemplate);

            // $arrCategoriesList e.g.:
            // Array
            // (
            //     [5] => Alle
            //     [2] => Holzbau
            // )       

            $objTemplate->projectCategories = $arrCategories;
            $objTemplate->projectCategoriesList = $arrCategoriesList;
        }

		






		return $objTemplate->parse();
	}


	/**
	 * Parse one or more items and return them as array
	 *
	 * @param \Model\Collection $objProjects
	 * @param boolean           $blnAddArchive
	 *
	 * @return array
	 */
	protected function parseProjects($objProjects, $blnAddArchive=false)
	{
		$limit = $objProjects->count();

		if ($limit < 1)
		{
			return array();
		}

		$count = 0;
		$arrArticles = array();

		while ($objProjects->next())
		{
			/** @var \ProjectsModel $objProject */
			$objProject = $objProjects->current();

			$arrArticles[] = $this->parseProject($objProject, $blnAddArchive, ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
		}

		return $arrArticles;
	}


	/**
	 * Return the meta fields of a project article as array
	 *
	 * @param \ProjectsModel $objProject
	 *
	 * @return array
	 */
	protected function getMetaFields($objProject)
	{
		$meta = StringUtil::deserialize($this->project_metaFields);

		if (!is_array($meta))
		{
			return array();
		}

		/** @var \PageModel $objPage */
		global $objPage;

		$return = array();

		foreach ($meta as $field)
		{
			switch ($field)
			{
				case 'date':
					$return['date'] = Date::parse($objPage->datimFormat, $objProject->date);
					break;

				// case 'author':
				// 	/** @var \UserModel $objAuthor */
				// 	if (($objAuthor = $objProject->getRelated('author')) !== null)
				// 	{
				// 		$return['author'] = $GLOBALS['TL_LANG']['MSC']['by'] . ' ' . $objAuthor->name;
				// 	}
				// 	break;

				case 'comments':
					// if ($objProject->noComments || !in_array('comments', \ModuleLoader::getActive()) || $objProject->source != 'default')
					// {
					// 	break;
					// }
					$intTotal = CommentsModel::countPublishedBySourceAndParent('tl_project', $objProject->id);
					$return['ccount'] = $intTotal;
					$return['comments'] = sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $intTotal);
					break;
			}
		}

		return $return;
	}


		/**
	 * Generate a URL and return it as string
	 *
	 * @param ProjectsModel $objItem
	 * @param boolean   $blnAddArchive
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
	 *             Use News::generateNewsUrl() instead.
	 */
	protected function generateProjectUrl($objItem, $blnAddArchive=false)
	{
		trigger_deprecation('contao/news-bundle', '4.1', 'Using "Contao\ModuleNews::generateNewsUrl()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\News::generateNewsUrl()" instead.');

		return Projects::generateProjectUrl($objItem, $blnAddArchive);
	}





	/**
	 * Generate a link and return it as string
	 *
	 * @param string     $strLink
	 * @param \ProjectModel $objProject
	 * @param boolean    $blnAddArchive
	 * @param boolean    $blnIsReadMore
	 *
	 * @return string
	 */
	protected function generateLink($strLink, $objProject, $blnAddArchive=false, $blnIsReadMore=false)
	{
		// Internal link
		if ($objProject->source != 'external')
		{
			return sprintf('<a href="%s" title="%s">%s%s</a>',
							$this->generateProjectUrl($objProject, $blnAddArchive),
							StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objProject->headline), true),
							$strLink,
							($blnIsReadMore ? '<span class="invisible"> '.$objProject->headline.'</span>' : ''));
		}

		// Encode e-mail addresses
		if (substr($objProject->url, 0, 7) == 'mailto:')
		{
			$strArticleUrl = StringUtil::encodeEmail($objProject->url);
		}

		// Ampersand URIs
		else
		{
			$strArticleUrl = ampersand($objProject->url);
		}

		/** @var \PageModel $objPage */
		global $objPage;

		// External link
		return sprintf('<a href="%s" title="%s"%s>%s</a>',
						$strArticleUrl,
						StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $strArticleUrl)),
						($objProject->target ? (($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"') : ''),
						$strLink);
	}
}
