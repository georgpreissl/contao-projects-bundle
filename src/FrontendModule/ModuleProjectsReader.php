<?php



namespace GeorgPreissl\Projects\FrontendModule;

use Contao\System;
use Contao\Input;
use Contao\Config;
use Contao\StringUtil;
use Contao\BackendTemplate;
use Contao\PageModel;
use Contao\Date;
use Contao\Environment;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use GeorgPreissl\Projects\Model\ProjectsModel;
use GeorgPreissl\Projects\Projects;
use GeorgPreissl\Projects\ProjectsSiblingNavigation;


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

			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['projectsreader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}


		// Return an empty string if "auto_item" is not set to combine list and reader on same page
		if (Input::get('auto_item') === null)
		{
			return '';
		}

		$this->projects_archives = $this->sortOutProtected(StringUtil::deserialize($this->projects_archives));


		if (empty($this->projects_archives) || !\is_array($this->projects_archives))
		{
			throw new InternalServerErrorException('The projectsreader ID ' . $this->id . ' has no archives specified.');
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

		$this->Template->projects = '';

		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

		// if the overview pages has been defined in the reader module settings
		if ($this->overviewPage && ($overviewPage = PageModel::findById($this->overviewPage)))
		{
			$this->Template->referer = $urlGenerator->generate($overviewPage);
			$this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['projectOverview'];
		}

		// Get the project item
		$objProject = ProjectsModel::findPublishedByParentAndIdOrAlias(Input::get('auto_item'), $this->projects_archives);

		// The project item does not exist
		if ($objProject === null)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Redirect if the project item has a target URL
		// Bei 'normalen' Projekten hat $objProject->source den Wert 'default'
		switch ($objProject->source)
		{
			case 'internal':
			case 'article':
			case 'external':
				throw new RedirectResponseException(System::getContainer()->get('contao.routing.content_url_generator')->generate($objProject, array(), UrlGeneratorInterface::ABSOLUTE_URL), 301);
		}

		// Set the default template
		if (!$this->projects_template)
		{
			$this->projects_template = 'project_full';
		}


		// Overwrite the page metadata (see #2853, #4955 and #87)
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if ($responseContext?->has(HtmlHeadBag::class))
		{
			$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
			$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

			if ($objProject->pageTitle)
			{
				// für das Projekt wurde ein eigener Meta-Titel vergeben, er soll nun auf der Seite verwendet werden
				$htmlHeadBag->setTitle($objProject->pageTitle); // Already stored decoded
			}
			elseif ($objProject->headline)
			{
				// es wurde kein Meta-Titel vergeben, daher wird die Headline als selbiger verwendet
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($objProject->headline));
			}

			if ($objProject->metaDescription)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($objProject->metaDescription));
			}
			elseif ($objProject->teaser)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($objProject->teaser));
			}

			if ($objProject->robots)
			{
				$htmlHeadBag->setMetaRobots($objProject->robots);
			}

			if ($objProject->canonicalLink)
			{
				
				$url = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objProject->canonicalLink);

				// Ensure absolute links
				if (!preg_match('#^https?://#', $url))
				{
					if (!$request = System::getContainer()->get('request_stack')->getCurrentRequest())
					{
						throw new \RuntimeException('The request stack did not contain a request');
					}

					$url = UrlUtil::makeAbsolute($url, $request->getUri());
				}

				$htmlHeadBag->setCanonicalUri($url);
				
			}
			elseif (!$this->projects_keepCanonical)
			{
				$htmlHeadBag->setCanonicalUri($urlGenerator->generate($objProject, array(), UrlGeneratorInterface::ABSOLUTE_URL));
			}
		}


		$strProject = $this->parseProject($objProject);
		$this->Template->project = $strProject;


		// dump($this->projects_order);
		// dump($this->projects_siblingNavigation);
		
		
		if($this->projects_siblingNavigation)
		{
			$this->Template = ProjectsSiblingNavigation::parseSiblingNavigation($this,$this->Template,$objProject);
		}
		


	}





}
