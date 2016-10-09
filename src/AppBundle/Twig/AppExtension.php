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

use AppBundle\Entity\User;
use EmperorNortonCommands\lib\Ddate;
use Twig_Extension;
use Twig_SimpleFunction;

class AppExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('ddate', [$this, 'ddate']),
            new Twig_SimpleFunction('getDisplayName', [$this, 'getDisplayName']),
        ];
    }

    /**
     * Outputs Discordian date.
     *
     * @see Ddate::ddate()
     *
     * @param string $format
     * @param string $date
     * @param string $locale
     * @return string
     */
    public function ddate($format = null, $date = null, $locale = 'en')
    {
        if (empty($date)) {
            $date = null;
        }
        $ddate = new Ddate();

        return $ddate->ddate($format, $date, $locale);
    }

    /**
     * Outputs first name if set, else username.
     *
     * @param User $user OPTIONAL
     * @return string
     */
    public function getDisplayName(User $user=null)
    {
        if (is_null($user)) {
            return '';
        }
        if (strlen($user->getFirstName())) {
            return $user->getFirstName() . ' ' . $user->getLastName();
        } else {
            return $user->getUsername();
        }
    }

    public function getName()
    {
        return 'app_extension';
    }
}
