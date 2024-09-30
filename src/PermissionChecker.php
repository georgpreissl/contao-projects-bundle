<?php



namespace GeorgPreissl\Projects;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Database;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PermissionChecker implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * PermissionChecker constructor.
     *
     * @param Connection            $db
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(Connection $db, TokenStorageInterface $tokenStorage)
    {
        $this->db = $db;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Return true if the user can manage project categories.
     *
     * @return bool
     */
    public function canUserManageCategories()
    {
        // dump($this->getUser()->hasAccess('manage', 'projectcategories'));
        return $this->getUser()->hasAccess('manage', 'projectcategories');
    }

    /**
     * Return true if the user can assign project categories.
     *
     * @return bool
     */
    public function canUserAssignCategories()
    {
       

        $user = $this->getUser();

        return $user->isAdmin || \in_array('tl_projects::categories', $user->alexf, true);
    }

    /**
     * Get the user default categories.
     *
     * @return array
     */
    public function getUserDefaultCategories()
    {
        
        $user = $this->getUser();

        return \is_array($user->projectcategories_default) ? $user->projectcategories_default : [];
    }

    /**
     * Get the user allowed roots. Return null if the user has no limitation.
     *
     * @return array|null
     */
    public function getUserAllowedRoots()
    {
        
        $user = $this->getUser();

        if ($user->isAdmin) {
            return null;
        }

        $rootIds = \array_map('intval', (array) $user->projectcategories_roots);

        if (empty($rootIds)) {
            return [];
        }

        $existingIds = $this->db->fetchFirstColumn('SELECT id FROM tl_projects_category WHERE id IN (?)', [$rootIds], [ArrayParameterType::INTEGER]);

        if (empty($existingIds)) {
            return [];
        }

        return array_intersect($rootIds, $existingIds);
    }

    /**
     * Return if the user is allowed to manage the project category.
     *
     * @param int $categoryId
     *
     * @return bool
     */
    public function isUserAllowedNewsCategory($categoryId)
    {
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return true;
        }

        /** @var Database $db */
        $db = $this->framework->createInstance(Database::class);

        $ids = $db->getChildRecords($roots, 'tl_projects_category', false, $roots);
        $ids = \array_map('intval', $ids);

        return \in_array((int) $categoryId, $ids, true);
    }

    /**
     * Add the category to allowed roots.
     *
     * @param int $categoryId
     */
    public function addCategoryToAllowedRoots($categoryId)
    {
        
        if (null === ($roots = $this->getUserAllowedRoots())) {
            return;
        }

        $categoryId = (int) $categoryId;
        $user = $this->getUser();

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->framework->getAdapter(StringUtil::class);

        // Add the permissions on group level
        if ('custom' !== $user->inherit) {
            $groups = $this->db->fetchAllAssociative('SELECT id, projectcategories, projectcategories_roots FROM tl_user_group WHERE id IN('.\implode(',', \array_map('intval', $user->groups)).')');

            foreach ($groups as $group) {
                $permissions = $stringUtil->deserialize($group['projectcategories'], true);

                if (\in_array('manage', $permissions, true)) {
                    $categoryIds = $stringUtil->deserialize($group['projectcategories_roots'], true);
                    $categoryIds[] = $categoryId;

                    $this->db->update('tl_user_group', ['projectcategories_roots' => \serialize($categoryIds)], ['id' => $group['id']]);
                }
            }
        }

        // Add the permissions on user level
        if ('group' !== $user->inherit) {
            $userData = $this->db->fetchAssociative('SELECT projectcategories, projectcategories_roots FROM tl_user WHERE id=?', [$user->id]);
            $permissions = $stringUtil->deserialize($userData['projectcategories'], true);

            if (\in_array('manage', $permissions, true)) {
                $categoryIds = $stringUtil->deserialize($userData['projectcategories_roots'], true);
                $categoryIds[] = $categoryId;

                $this->db->update('tl_user', ['projectcategories_roots' => \serialize($categoryIds)], ['id' => $user->id]);
            }
        }

        // Add the new element to the user object
        $user->projectcategories_roots = \array_merge($roots, [$categoryId]);
    }

    /**
     * Get the user.
     *
     * @throws \RuntimeException
     *
     * @return BackendUser
     */
    private function getUser()
    {
        
        if (null === $this->tokenStorage) {
            throw new \RuntimeException('No token storage provided');
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('No token provided');
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }
}
