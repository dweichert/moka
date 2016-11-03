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
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class AppExtension extends Twig_Extension
{
    private $translations = [
        'de' => [
            'once' => 'einmal',
            'twice' => 'zweimal',
            'three times' => 'dreimal',
            'four times' => 'viermal',
            'five times' => 'fünfmal',
            'six times' => 'sechsmal',
            'seven times' => 'siebenmal',
            'eight times' => 'achtmal',
            'nine times' => 'neunmal',
            'ten times' => 'zehnmal',
            'eleven times' => 'elfmal',
            'twelve times' => 'zwölfmal',
            'thirteen times' => 'dreizehnmal',
            'fourteen times' => 'vierzehnmal',
            'fifteen times' => 'fünfzehnmal',
            'sixteen times' => 'sechzehnmal',
            'seventeen times' => 'siebzehnmal',
            'eighteen times' => 'achtzehnmal',
            'nineteen times' => 'neunzehnmal',
            'twenty times' => 'zwanzigmal',
            'twenty-one times' => 'einundzwanzigmal',
            'twenty-two times' => 'zweiundzwanzigmal',
            'twenty-three times' => 'dreiundzwanzigmal',
            'unlimited' => 'unbegrenzt',
            ' times' => ' Mal',
        ]
    ];

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('ddate', [$this, 'ddate']),
            new Twig_SimpleFunction('getDisplayName', [$this, 'getDisplayName']),
        ];
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('abbreviate', [$this, 'abbreviateFilter']),
            new Twig_SimpleFilter('numOPledges', [$this, 'numOPledgesFilter'])
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
     * @param bool $firstnameOnly OPTIONAL
     * @return string
     */
    public function getDisplayName(User $user=null, $firstnameOnly = false)
    {
        if (is_null($user)) {
            return '';
        }
        if (!$firstnameOnly && strlen($user->getFirstName()) && strlen($user->getLastName())) {
            return $user->getFirstName() . ' ' . $user->getLastName();
        } elseif (strlen($user->getFirstName())) {
            return $user->getFirstName();
        } else {
            return $user->getUsername();
        }
    }

    /**
     * Abbreviates string.
     *
     * @param string $string
     * @param int $maxChars OPTIONAL
     * @return string
     */
    public function abbreviateFilter($string, $maxChars = 7) {
        $strlen = strlen($string);
        if ($strlen > $maxChars) {
            return substr($string, 0, 3) . '…' . substr($string, -3);
        }
        return $string;
    }

    /**
     * Translates integer to number of pledges into number of times as words.
     *
     * @param string $string
     * @param string $locale OPTIONAL
     * @return string
     */
    public function numOPledgesFilter($string, $locale = 'en') {
        switch ($string) {
            case '0':
                return $this->translate('once', $locale);
            case '1':
                return $this->translate('twice', $locale);
            case '2':
                return $this->translate('three times', $locale);
            case '3':
                return $this->translate('four times', $locale);
            case '4':
                return $this->translate('five times', $locale);
            case '5':
                return $this->translate('six times', $locale);
            case '6':
                return $this->translate('seven times', $locale);
            case '7':
                return $this->translate('eight times', $locale);
            case '8':
                return $this->translate('nine times', $locale);
            case '9':
                return $this->translate('ten times', $locale);
            case '10':
                return $this->translate('eleven times', $locale);
            case '11':
                return $this->translate('twelve times', $locale);
            case '12':
                return $this->translate('thirteen times', $locale);
            case '13':
                return $this->translate('fourteen times', $locale);
            case '14':
                return $this->translate('fifteen times', $locale);
            case '15':
                return $this->translate('sixteen times', $locale);
            case '16':
                return $this->translate('seventeen times', $locale);
            case '17':
                return $this->translate('eighteen times', $locale);
            case '18':
                return $this->translate('nineteen times', $locale);
            case '19':
                return $this->translate('twenty times', $locale);
            case '20':
                return $this->translate('twenty-one times', $locale);
            case '21':
                return $this->translate('twenty-two times', $locale);
            case '22':
                return $this->translate('twenty-three times', $locale);
            case '-1':
                return $this->translate('unlimited', $locale);
            default:
                return $string . $this->translate(' times', $locale);
        }
    }

    public function getName()
    {
        return 'app_extension';
    }

    /**
     * Translates strings.
     *
     * @param $string
     * @param $locale
     * @return string
     */
    private function translate($string, $locale)
    {
        if (isset($this->translations[$locale][$string])) {
            return $this->translations[$locale][$string];
        }

        return $string;
    }
}
