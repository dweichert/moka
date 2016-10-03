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
use Assert\Assertion;
use Assert\AssertionFailedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

class PledgeController extends Controller
{
    /**
     * @Route("/{_locale}/pledge", requirements={"_locale" = "en|de"}, name="pledge")
     * @Method("POST")
     */
    public function indexAction(Request $request)
    {
        $token = $request->request->get('_csrf_token');
        $csrfToken = new CsrfToken('missing_items', $token);
        if (!$this->isCsrfTokenValid('missing_items', $csrfToken)) {
            $this->addFlash('error', 'Invalid request, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        try {
            $id = $request->get('pledged-item');
            Assertion::integerish($id);
        } catch (AssertionFailedException $e) {
            $this->addFlash('error', 'Invalid item, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        $item = $this->getDoctrine()->getRepository('AppBundle:Item')->find($id);
        if (is_null($item))
        {
            $this->addFlash('error', 'Could not find pledged item, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        if ($item->getContributor())
        {
            $this->addFlash('error', 'Item has already been pledged by someone else. Please choose another item.');
            return $this->redirectToRoute('missing_items');
        }

        var_dump($request->get('user-street-address-1'));
        var_dump($request->get('user-street-address-2'));
        var_dump($request->get('user-postal-code'));
        var_dump($request->get('user-city'));
        var_dump($request->get('user-country'));
        var_dump($request->get('user-phone'));
        var_dump($request->get('user-mobile'));

        die;


        $view = $request->getLocale() == 'de' ? ':pledge:success.de.html.twig' : ':pledge:success.en.html.twig';
        /** @var User $contributor */
        $contributor = $this->getUser();
        $item->setContributor($contributor);
        $em = $this->getDoctrine()->getManager();
        $em->persist($item);
        $em->flush();

        return $this->render($view, [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..'),
            'item' => $item,
            'items' => $contributor->getItems()->toArray(),
        ]);
    }
}
