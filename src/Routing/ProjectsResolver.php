<?php


declare(strict_types=1);


namespace GeorgPreissl\Projects\Routing;

use Contao\ArticleModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use GeorgPreissl\Projects\Model\ProjectsArchiveModel;
use GeorgPreissl\Projects\Model\ProjectsModel;
use Contao\PageModel;



class ProjectsResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof ProjectsModel) {
            return null;
        }

        switch ($content->source) {
            // Link to an external page
            case 'external':
                return ContentUrlResult::url($content->url);

            // Link to an internal page
            case 'internal':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                return ContentUrlResult::redirect($pageAdapter->findPublishedById($content->jumpTo));

            // Link to an article
            case 'article':
                $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

                return ContentUrlResult::redirect($articleAdapter->findPublishedById($content->articleId));
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $archiveAdapter = $this->framework->getAdapter(ProjectsArchiveModel::class);

        // Link to the default page
        return ContentUrlResult::resolve(
            $pageAdapter->findPublishedById((int) $archiveAdapter->findById($content->pid)?->jumpTo)
        );
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof ProjectsModel) {
            return [];
        }

        return ['parameters' => '/'.($content->alias ?: $content->id)];
    }
}