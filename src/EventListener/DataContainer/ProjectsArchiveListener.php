<?php

// declare(strict_types=1);

namespace GeorgPreissl\Projects\EventListener\DataContainer;

// use GeorgPreissl\Projects\PermissionChecker;


// use GeorgPreissl\Projects\Security\ProjectsCategoriesPermissions;
// use Contao\ArrayUtil;
// use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
// use Symfony\Bundle\SecurityBundle\Security;

// #[AsCallback('tl_projects_archive', 'config.onload')]
// class NewsArchiveOperationListener
// {
//     public function __construct(private readonly Security $security)
//     {
//     }

//     public function __invoke(): void
//     {
//         if (!$this->security->isGranted(NewsCategoriesPermissions::USER_CAN_MANAGE_CATEGORIES)) {
//             return;
//         }

//         ArrayUtil::arrayInsert(
//             $GLOBALS['TL_DCA']['tl_news_archive']['list']['global_operations'], 1, [
//                 'categories' => [
//                     'label' => &$GLOBALS['TL_LANG']['tl_news_archive']['categories'],
//                     'href' => 'table=tl_news_category',
//                     'icon' => 'bundles/codefognewscategories/icon.png',
//                     'attributes' => 'onclick="Backend.getScrollOffset()"',
//                 ],
//             ],
//         );
//     }
// }



class ProjectsArchiveListener
{
    /**
     * @var PermissionChecker
     */
    private $permissionChecker;

    /**
     * NewsArchiveListener constructor.
     *
     * @param PermissionChecker $permissionChecker
     */
    public function __construct(PermissionChecker $permissionChecker)
    {
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * On data container load.
     */
    public function onLoadCallback()
    {
        dump('ProjectsArchiveListener');
        if (!$this->permissionChecker->canUserManageCategories()) {
            unset($GLOBALS['TL_DCA']['tl_projects_archive']['list']['global_operations']['categories']);
        }
    }
}


