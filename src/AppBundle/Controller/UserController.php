<?php
/**
 * This file is part of moka.
 *
 * (c) David Weichert <info@davidweichert.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Item;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Route("/{_locale}/admin/user/{id}", requirements={"_locale" = "en|de"}, name="user_details")
     * @Method("GET")
     */
    public function detailsAction(Request $request, $id)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);

        $view = $request->getLocale() == 'de' ? ':user:details.de.html.twig' : ':user:details.en.html.twig';

        return $this->render(
            $view,
            ['user' => $user]
        );
    }
}
