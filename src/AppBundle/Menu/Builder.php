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

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $translations = [
        'Home' => 'Startseite',
    ];

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $locale = $this->container->get('router.request_context')->getParameter('_locale');
        $menu = $factory->createItem('root', array('childrenAttributes' => array('class' => 'nav navbar-nav bs-navbar-collapse')));
        $menu->addChild('Home', array('route' => 'homepage', 'label' => $this->getLabel($locale, 'Home')));

        return $menu;
    }

    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $baseurl = $this->container->get('router.request_context')->getBaseUrl();
        $pathinfo = $this->container->get('router.request_context')->getPathinfo();
        $locale = $this->container->get('router.request_context')->getParameter('_locale');

        $menu = $factory->createItem('root', array('childrenAttributes' => array('class' => 'nav navbar-nav bs-navbar-collapse navbar-right')));

        $menu->addChild('Language Toggler', array('uri' => $this->getLanguageTogglerUri($baseurl, $pathinfo, $locale), 'label' => $this->getLanguageTogglerLinktext($locale)));
        return $menu;
    }

    private function getLabel($locale, $englishLabel)
    {
        switch ($locale) {
            case 'de':
                return $this->translations[$englishLabel];
            default:
                return $englishLabel;
        }
    }

    private function getLanguageTogglerUri($baseurl, $pathinfo, $locale)
    {
        $parts = explode('/', $pathinfo);
        switch ($locale) {
            case 'en':
                $parts[1] = 'de';
                break;
            default:
                $parts[1] = 'en';
                break;
        }
        $uri = implode('/', $parts);
        $uri = $baseurl . $uri;

        return $uri;
    }

    private function getLanguageTogglerLinktext($locale)
    {
        switch ($locale) {
            case 'en':
                return 'Deutsch';
                break;
            default:
                return 'English';
                break;
        }
    }
}
