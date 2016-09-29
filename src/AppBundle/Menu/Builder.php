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
use Knp\Menu\FactoryInterface;
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
