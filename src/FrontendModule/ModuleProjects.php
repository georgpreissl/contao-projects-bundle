<?php



namespace GeorgPreissl\Projects\FrontendModule;

use Contao\StringUtil;
use Contao\System;
use Contao\Module;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\ContentModel;
use Contao\CommentsModel;
use Contao\File;
use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\ProjectsCategoriesManager;
use GeorgPreissl\Projects\Model\ProjectsCategoryModel;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;
use GeorgPreissl\Projects\Model\ProjectsModel;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


abstract class ModuleProjects extends Module
{


	// private static $arrUrlCache = array();


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
		global $objPage;

		$objTemplate = new FrontendTemplate($this->projects_template ?: 'project_latest');
		$objTemplate->setData($objProject->row());

		if ($objProject->cssClass)
		{
			$strClass = ' ' . $objProject->cssClass . $strClass;
		}

		if ($objProject->featured)
		{
			$strClass = ' featured' . $strClass;
		}
		
		$url = $this->generateContentUrl($objProject, $blnAddArchive);

		// parse the gallery
		$this->multiSRC = StringUtil::deserialize($objProject->multiSRC);

		// Get the file entries from the database
		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		// dump($this->multiSRC);
		// dump($this->objFiles);

		$images = array();
		$arrGallery = array();
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
// dump($objProject->sortBy);

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
				break;

			case 'random':
				shuffle($images);
				$this->Template->isRandomOrder = true;
				break;
		}

		$images = array_values($images);


		$i = 0;
		$strLightboxId = 'lb' . $objProject->id;


		// $imgSize = $objProject->size ?: null;

		// // Override the default image size
		// if ($this->projects_imgSizeGallery)
		// {
		// 	$size = StringUtil::deserialize($this->projects_imgSizeGallery);

		// 	if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
		// 	{
		// 		$imgSize = $this->imgSize;
		// 	}
		// }

		$figureBuilder = System::getContainer()
			->get('contao.image.studio')
			->createFigureBuilder()
			->setSize($this->projects_imgSizeGallery);


		foreach ($images as $image) {


				$figure = $figureBuilder
				->fromId($image['id'])
				->build();

				$cellData = $figure->getLegacyTemplateData();
				$cellData['figure'] = $figure;				
				$arrGallery[] = (object) $cellData;

		}
// dump($intCount);
		$objTemplate->gallery = $arrGallery;

		$objTemplate->class = $strClass;
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
		$objTemplate->archive = ProjectsArchiveModel::findById($objProject->pid);
		$objTemplate->count = $intCount; // see #5708
		$objTemplate->text = '';
		$objTemplate->hasText = true;
		$objTemplate->hasTeaser = false;

		if (null !== $url)
		{
			$objTemplate->linkHeadline = $this->generateLink($objProject->headline, $objProject, $blnAddArchive);
			$objTemplate->more = $this->generateLink($objProject->linkText ?: $GLOBALS['TL_LANG']['MSC']['more'], $objProject, $blnAddArchive, true);
			$objTemplate->link = $url;
		}

		// Clean the RTE output
		if ($objProject->teaser)
		{
			$objTemplate->hasTeaser = true;
			$objTemplate->teaser = $objArticle->teaser;
			$objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
		}

		// Display the "read more" button for external/article links
		if ($objProject->source != 'default')
		{
			$objTemplate->text = true;
			$objTemplate->hasText = null !== $url;
			$objTemplate->hasReader = false;
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

		

		// Add the meta information
		$objTemplate->timestamp = $objProject->date;
		$objTemplate->datetime = date('Y-m-d\TH:i:sP', $objProject->date);
		$objTemplate->addImage = false;
		// dump($objTemplate->target);

		// $arrMeta = $this->getMetaFields($objProject);
		// $objTemplate->hasMetaFields = !empty($arrMeta);


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
				// Rebuild with link to the project if we are not in a
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

		// schema.org information
		$objTemplate->getSchemaOrgData = static function () use ($objProject, $objTemplate): array {
			$jsonLd = Projects::getSchemaOrgData($objProject);

			if ($objTemplate->addImage && $objTemplate->figure)
			{
				$jsonLd['image'] = $objTemplate->figure->getSchemaOrgData();
			}

			return $jsonLd;
		};

		$arrData = $objProject->row();
		// $arrData enthält alle Infos zu einem Projekt
		// dump($arrData);
        




		
		$categories = ProjectsCategoryModel::findPublishedByProject($arrData['id']);

		if($categories !== null){

			// to generate the category links on the details page, we need the page where the projects are listed
			$objOverviewPage = PageModel::findPublishedById($this->overviewPage);

			// use the manager for category specific links
			$this->manager = System::getContainer()->get(ProjectsCategoriesManager::class);

			$arrCategories = array();
			foreach ($categories as $category) {
				if (null !== ($targetPage = $this->manager->getTargetPage($category))) {
					$url = $targetPage->getFrontendUrl();
				} else {
					if (null !== $objOverviewPage) {
						$url = $this->manager->generateUrl($category, $objOverviewPage);
					}
				}
				$arrCategory = array();
				$arrCategory['url'] = $url;		
				$arrCategory['title'] = $category->frontendTitle ? $category->frontendTitle : $category->title;
				$arrCategory['cssClass'] = $category->cssClass;			
				$arrCategories[] = $arrCategory;
			}
			$objTemplate->categories = $arrCategories;	
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
	 * @param string    $strLink
	 * @param NewsModel $objArticle
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnIsReadMore
	 *
	 * @return string
	 */
	protected function generateLink($strLink, $objArticle, $blnAddArchive=false, $blnIsReadMore=false)
	{
		$blnIsInternal = $objArticle->source != 'external';
		// dump($blnIsInternal);
		$strReadMore = $blnIsInternal ? $GLOBALS['TL_LANG']['MSC']['readMore'] : $GLOBALS['TL_LANG']['MSC']['open'];
		$strArticleUrl = $this->generateContentUrl($objArticle, $blnAddArchive);

		return \sprintf(
			'<a href="%s" title="%s"%s>%s%s</a>',
			$strArticleUrl,
			StringUtil::specialchars(\sprintf($strReadMore, $blnIsInternal ? $objArticle->headline : $strArticleUrl), true),
			$objArticle->target && !$blnIsInternal ? ' target="_blank" rel="noreferrer noopener"' : '',
			$strLink,
			$blnIsReadMore && $blnIsInternal ? '<span class="invisible"> ' . $objArticle->headline . '</span>' : ''
		);
	}

	private function generateContentUrl(ProjectsModel $content, bool $addArchive): string|null
	{
		
		$parameters = array();

		// Add the current archive parameter (projects archive)
		if ($addArchive && Input::get('month'))
		{
			$parameters['month'] = Input::get('month');
		}
		
		try
		{
			return System::getContainer()->get('contao.routing.content_url_generator')->generate($content, $parameters);
		}
		catch (ExceptionInterface)
		{
			return null;
		}
	}


}
