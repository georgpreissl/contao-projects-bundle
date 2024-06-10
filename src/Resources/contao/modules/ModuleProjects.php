<?php



namespace GeorgPreissl\Projects;

use Contao\ArrayUtil;


abstract class ModuleProjects extends \Module
{


	private static $arrUrlCache = array();


	protected function sortOutProtected($arrArchives)
	{
		if (BE_USER_LOGGED_IN || !is_array($arrArchives) || empty($arrArchives))
		{
			return $arrArchives;
		}

		$this->import('FrontendUser', 'User');
		$objArchive = ProjectsArchiveModel::findMultipleByIds($arrArchives);
		$arrArchives = array();

		if ($objArchive !== null)
		{
			while ($objArchive->next())
			{
				if ($objArchive->protected)
				{
					if (!FE_USER_LOGGED_IN)
					{
						continue;
					}

					$groups = deserialize($objArchive->groups);

					if (!is_array($groups) || empty($groups) || !count(array_intersect($groups, $this->User->groups)))
					{
						continue;
					}
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
		$objTemplate = new \FrontendTemplate($this->projects_template);
		$objTemplate->setData($objProject->row());



		// parse the gallery
		$this->multiSRC = \StringUtil::deserialize($objProject->multiSRC);

		// Get the file entries from the database
		$this->objFiles = \FilesModel::findMultipleByUuids($this->multiSRC);

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
				if (isset($images[$objFiles->path]) || !file_exists(\System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path))
				{
					continue;
				}

				// Check whether the user has selected files or a folder
				if ($objFiles->type == 'file')
				{
					$objFile = new \File($objFiles->path);

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
					$objSubfiles = \FilesModel::findByPid($objFiles->uuid, array('order' => 'name'));

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

						$objFile = new \File($objSubfiles->path);
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
		$objTemplate->link = $this->generateProjectUrl($objProject, $blnAddArchive);
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
				$objTemplate->teaser = \StringUtil::toXhtml($objProject->teaser);
			}
			else
			{
				$objTemplate->teaser = \StringUtil::toHtml5($objProject->teaser);
			}

			$objTemplate->teaser = \StringUtil::encodeEmail($objTemplate->teaser);
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
				$objElement = \ContentModel::findPublishedByPidAndTable($id, 'tl_projects');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strText .= $this->getContentElement($objElement->current());
					}
				}

				return $strText;
			};

			$objTemplate->hasText = (\ContentModel::countPublishedByPidAndTable($objProject->id, 'tl_projects') > 0);
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

		$objTemplate->addImage = true;
		$objProject->addImage = true;

		// Add an image
		if ($objProject->addImage && $objProject->singleSRC != '')
		{

			$objModel = \FilesModel::findByUuid($objProject->singleSRC);

			if ($objModel === null)
			{
				if (!\Validator::isUuid($objProject->singleSRC))
				{
					$objTemplate->text = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
				}
			}
			elseif (is_file(TL_ROOT . '/' . $objModel->path))
			{

				// Do not override the field now that we have a model registry (see #6303)
				$arrArticle = $objProject->row();

				// Override the default image size
				if ($this->imgSize != '')
				{
					$size = deserialize($this->imgSize);

					if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]))
					{
						$arrArticle['size'] = $this->imgSize;
					}
				}

				$arrArticle['singleSRC'] = $objModel->path;
				$this->addImageToTemplate($objTemplate, $arrArticle);

				// Link to the project if no image link has been defined (see #30)
				if (!$objTemplate->fullsize && !$objTemplate->imageUrl)
				{
					// Unset the image title attribute
					$picture = $objTemplate->picture;
					unset($picture['title']);
					$objTemplate->picture = $picture;

					// Link to the project
					$objTemplate->href = $objTemplate->link;
					$objTemplate->linkTitle = \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objProject->headline), true);

					// If the external link is opened in a new window, open the image link in a new window, too (see #210)
					if ($objTemplate->source == 'external' && $objTemplate->target && strpos($objTemplate->attributes, 'target="_blank"') === false)
					{
						$objTemplate->attributes .= ' target="_blank"';
					}
				}


			}
		}

		$objTemplate->enclosure = array();

		// Add enclosures
		if ($objProject->addEnclosure)
		{
			$this->addEnclosuresToTemplate($objTemplate, $objProject->row());
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['parseArticles']) && is_array($GLOBALS['TL_HOOKS']['parseArticles']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseArticles'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($objTemplate, $objProject->row(), $this);
			}
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
		$meta = deserialize($this->project_metaFields);

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
					$return['date'] = \Date::parse($objPage->datimFormat, $objProject->date);
					break;

				case 'author':
					/** @var \UserModel $objAuthor */
					if (($objAuthor = $objProject->getRelated('author')) !== null)
					{
						$return['author'] = $GLOBALS['TL_LANG']['MSC']['by'] . ' ' . $objAuthor->name;
					}
					break;

				case 'comments':
					if ($objProject->noComments || !in_array('comments', \ModuleLoader::getActive()) || $objProject->source != 'default')
					{
						break;
					}
					$intTotal = \CommentsModel::countPublishedBySourceAndParent('tl_project', $objProject->id);
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
	 * @param \ProjectModel $objItem
	 * @param boolean    $blnAddArchive
	 *
	 * @return string
	 */
	protected function generateProjectUrl($objItem, $blnAddArchive=false)
	{
		$strCacheKey = 'id_' . $objItem->id;

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
				if (substr($objItem->url, 0, 7) == 'mailto:')
				{
					self::$arrUrlCache[$strCacheKey] = \StringUtil::encodeEmail($objItem->url);
				}
				else
				{
					self::$arrUrlCache[$strCacheKey] = ampersand($objItem->url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) !== null)
				{
					/** @var \PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = ampersand($objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objProject = \ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null && ($objPid = $objProject->getRelated('pid')) !== null)
				{
					/** @var \PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = ampersand($objPid->getFrontendUrl('/articles/' . ((!\Config::get('disableAlias') && $objProject->alias != '') ? $objProject->alias : $objProject->id)));
				}
				break;
		}



		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			// $objPage = \PageModel::findWithDetails($objItem->getRelated('pid')->jumpTo);
			$objPage = \PageModel::findWithDetails(10);

			if ($objPage === null)
			{
				self::$arrUrlCache[$strCacheKey] = ampersand(\Environment::get('request'), true);
			}
			else
			{
				self::$arrUrlCache[$strCacheKey] = ampersand($objPage->getFrontendUrl(((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/' : '/items/') . ((!\Config::get('disableAlias') && $objItem->alias != '') ? $objItem->alias : $objItem->id)));
			}

			// Add the current archive parameter (project archive)
			if ($blnAddArchive && \Input::get('month') != '')
			{
				self::$arrUrlCache[$strCacheKey] .= (\Config::get('disableAlias') ? '&amp;' : '?') . 'month=' . \Input::get('month');
			}
		}
					// printf('<pre>%s</pre>', print_r(self::$arrUrlCache[$strCacheKey],true));

		return self::$arrUrlCache[$strCacheKey];
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
							specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objProject->headline), true),
							$strLink,
							($blnIsReadMore ? '<span class="invisible"> '.$objProject->headline.'</span>' : ''));
		}

		// Encode e-mail addresses
		if (substr($objProject->url, 0, 7) == 'mailto:')
		{
			$strArticleUrl = \StringUtil::encodeEmail($objProject->url);
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
						specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $strArticleUrl)),
						($objProject->target ? (($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"') : ''),
						$strLink);
	}
}
