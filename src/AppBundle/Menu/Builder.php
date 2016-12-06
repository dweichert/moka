<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Menu;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\RequestContext;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $translations = [
        'Home' => 'Startseite',
        'Sign in' => 'Anmelden',
        'Account' => 'Konto',
        'Logout' => 'Abmelden',
        'My pledged items' => 'Meine Beiträge',
        'Administration' => 'Administration',
        'Items' => 'Beiträge',
        'PLEDGE' => 'SPENDEN',
        'Background' => 'Hintergrund',
    ];

    /**
     * @var RequestContext
     */
    private $routerRequestContext;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $locale;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem(
            'root', ['childrenAttributes' => ['class' => 'nav navbar-nav bs-navbar-collapse']]
        );
        $menu->addChild('Home', ['route' => 'homepage', 'label' => $this->getLabel('Home')]);
        $menu
            ->addChild('Cosmic Trigger', ['route' => 'missing_items', 'label' => $this->getLabel('PLEDGE')])
            ->setLinkAttributes(['class' => 'pledge-menu-item', 'title' => 'Horkos is Waiting…']);
        $backgroundMenuItem = $menu->addChild('Background', ['label' => $this->getLabel('Background')])
            ->setAttribute('dropdown', true);
        $backgroundMenuItem->addChild(
            'Cosmic Trigger Play', ['uri' => 'http://cosmictriggerplay.com', 'label' => 'Cosmic Trigger | The Play']
        );
        $this->addAdminMenu($menu);

        return $menu;
    }

    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem(
            'root', ['childrenAttributes' => ['class' => 'nav navbar-nav bs-navbar-collapse navbar-right']]
        );

        if (!$this->getUser()) {
            $menu->addChild('Login', ['route' => 'fos_user_security_login', 'label' => $this->getLabel('Sign in')])
                ->setAttribute('icon', 'fa fa-user');
        } else {
            $userMenuItem = $menu
                ->addChild('CurrentUser', ['label' => $this->getDisplayName()])
                ->setAttribute('dropdown', true)
                ->setAttribute('icon', 'fa fa-user');
            $userMenuItem->addChild(
                'Profile', [
                    'route' => 'fos_user_profile_show',
                    'label' => $this->getLabel('Account')
                ]
            );
            $userMenuItem->addChild(
                'My Pledged Items', [
                    'route' => 'pledge_list',
                    'label' => $this->getLabel('My pledged items')
                ]
            );
            $userMenuItem->addChild(
                'Logout', [
                    'route' => 'fos_user_security_logout',
                    'label' => $this->getLabel('Logout')
                ]
            );
        }
        $menu->addChild(
            'Language Toggler', [
                'uri' => $this->getLanguageTogglerUri(),
                'label' => $this->getLanguageTogglerLinktext()
            ]
        );

        return $menu;
    }

    private function addAdminMenu(ItemInterface $mainMenu)
    {
        if (!$this->getUser()) {
            return;
        }
        if (in_array(UserRepository::ROLE_ADMIN, $this->getUser()->getRoles())) {
            $adminMenuItem = $mainMenu
                ->addChild('Administration', ['label' => $this->getLabel('Administration')])
                ->setAttribute('dropdown', true);
            $adminMenuItem->addChild(
                'Items', [
                    'route' => 'item_list',
                    'label' => $this->getLabel('Items')
                ]
            );
        }
    }

    /**
     * @return RequestContext
     */
    private function getRouterRequestContext()
    {
        if (is_null($this->routerRequestContext)) {
            $this->routerRequestContext = $this->container->get('router.request_context');
        }

        return $this->routerRequestContext;
    }

    /**
     * Returns user entity or false, if no user is logged in.
     *
     * @return User|bool
     */
    private function getUser()
    {
        if (is_null($this->user)) {
            $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        }

        if ($this->user === 'anon.') {
            return false;
        }

        return $this->user;
    }

    /**
     * Returns first and lastname if present, else user name.
     *
     * @return string
     */
    private function getDisplayName()
    {
        $displayName = $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName();
        if (' ' != $displayName) {
            return $displayName;
        }

        return $this->getUser()->getUsername();
    }

    /**
     * @return string
     */
    private function getLocale()
    {
        if (is_null($this->locale)) {
            $this->locale = $this->getRouterRequestContext()->getParameter('_locale') ?: 'en';
        }

        return $this->locale;
    }

    /**
     * @param $englishLabel
     * @return string
     */
    private function getLabel($englishLabel)
    {
        switch ($this->getLocale()) {
            case 'de':
                return $this->translations[$englishLabel];
            default:
                return $englishLabel;
        }
    }

    /**
     * @return string
     */
    private function getLanguageTogglerUri()
    {
        $parts = explode('/', $this->getRouterRequestContext()->getPathinfo());
        switch ($this->getLocale()) {
            case 'en':
                $parts[1] = 'de';
                break;
            default:
                $parts[1] = 'en';
                break;
        }
        $uri = implode('/', $parts);
        $uri = $this->getRouterRequestContext()->getBaseUrl() . $uri;

        return $uri;
    }

    /**
     * @return string
     */
    private function getLanguageTogglerLinktext()
    {
        switch ($this->getLocale()) {
            case 'en':
                return 'Deutsch';
                break;
            default:
                return 'English';
                break;
        }
    }
}
