<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig;

use EmperorNortonCommands\lib\Ddate;
use Twig_Extension;
use Twig_SimpleFunction;

class AppExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('ddate', array($this, 'ddate')),
        );
    }

    public function ddate($format = null, $date = null, $locale = 'en')
    {
        if (empty($date)) {
            $date = null;
        }
        $ddate = new Ddate();

        return $ddate->ddate($format, $date, $locale);
    }

    public function getName()
    {
        return 'app_extension';
    }
}
