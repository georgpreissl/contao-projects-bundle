<?php



namespace GeorgPreissl\Projects\FrontendModule;

use Contao\System;
use Contao\Input;
use Contao\Config;
use Contao\StringUtil;
use Contao\BackendTemplate;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Symfony\Component\HttpFoundation\Request;
use GeorgPreissl\Projects\Model\ProjectsModel;

/**
 * Front end module "project reader".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleProjectsReader extends ModuleProjects
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_projectsreader';


	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['projectsreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			Input::setGet('items', Input::get('auto_item'));
		}

		// Do not index or cache the page if no project item has been specified
		if (!Input::get('items'))
		{
			/** @var \PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		$this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives));

		// Do not index or cache the page if there are no archives
		if (!is_array($this->projects_archives) || empty($this->projects_archives))
		{
			/** @var \PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var \PageModel $objPage */
		global $objPage;

		$this->Template->articles = '';
		$this->Template->referer = 'javascript:history.go(-1)';
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		// Get the project item
		$objArticle = ProjectsModel::findPublishedByParentAndIdOrAlias(Input::get('items'), $this->projects_archives);

		if (null === $objArticle)
		{
			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();
			$objHandler->generate($objPage->id);
		}

		$arrProject = $this->parseProject($objArticle);
		$this->Template->articles = $arrProject;

		// Overwrite the page title (see #2853 and #4955)
		if ($objArticle->headline != '')
		{
			$objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objArticle->headline));
		}

		// Overwrite the page description
		if ($objArticle->teaser != '')
		{
			$objPage->description = $this->prepareMetaDescription($objArticle->teaser);
		}

		// HOOK: comments extension required
		// if ($objArticle->noComments || !in_array('comments', \ModuleLoader::getActive()))
		// {
		// 	$this->Template->allowComments = false;

		// 	return;
		// }

		/** @var \ProjectArchiveModel $objArchive */
		$objArchive = $objArticle->getRelated('pid');
		$this->Template->allowComments = $objArchive->allowComments;

		// Comments are not allowed
		if (!$objArchive->allowComments)
		{
			return;
		}

		// Adjust the comments headline level
		$intHl = min(intval(str_replace('h', '', $this->hl)), 5);
		$this->Template->hlc = 'h' . ($intHl + 1);

		$this->import('Comments');
		$arrNotifies = array();

		// Notify the system administrator
		if ($objArchive->notify != 'notify_author')
		{
			$arrNotifies[] = $GLOBALS['TL_ADMIN_EMAIL'];
		}

		// Notify the author
		if ($objArchive->notify != 'notify_admin')
		{
			/** @var \UserModel $objAuthor */
			if (($objAuthor = $objArticle->getRelated('author')) !== null && $objAuthor->email != '')
			{
				$arrNotifies[] = $objAuthor->email;
			}
		}

		$objConfig = new \stdClass();

		$objConfig->perPage = $objArchive->perPage;
		$objConfig->order = $objArchive->sortOrder;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $objArchive->requireLogin;
		$objConfig->disableCaptcha = $objArchive->disableCaptcha;
		$objConfig->bbcode = $objArchive->bbcode;
		$objConfig->moderate = $objArchive->moderate;

		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, 'tl_project', $objArticle->id, $arrNotifies);




		// Overwrite the page metadata (see #2853, #4955 and #87)
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();


		if ($responseContext && $responseContext->has(HtmlHeadBag::class))
		{
			/** @var HtmlHeadBag $htmlHeadBag */
			$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
			$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

			if ($objProject->pageTitle)
			{
				$htmlHeadBag->setTitle($objProject->pageTitle); // Already stored decoded
			}
			elseif ($objProject->headline)
			{
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($objProject->headline));
			}

			if ($objProject->shortDescription)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($objProject->shortDescription));
			}
			elseif ($objProject->longDescription)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($objProject->longDescription));
			}

			if ($objProject->robots)
			{
				$htmlHeadBag->setMetaRobots($objProject->robots);
			}
		}


	}
}
