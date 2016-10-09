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
use Assert\Assertion;
use Assert\AssertionFailedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

class ItemController extends Controller
{
    /**
     * @Route("/{_locale}/item/list", requirements={"_locale" = "en|de"}, name="missing_items")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $request->getSession()->set('_security.main.target_path', $this->generateUrl('missing_items'));
        }
        return $this->render(
            $request->getLocale() == 'de' ? 'item/index.de.html.twig' : 'item/index.en.html.twig', [
                'items' => $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor(),
                'user' => $user,
                'address_confirmed' => (bool)$request->getSession()->get('address_confirmed', false)
            ]
        );
    }

    /**
     * @Route("/{_locale}/admin/item/list", requirements={"_locale" = "en|de"}, name="item_list")
     * @Method("GET")
     */
    public function adminListAction(Request $request)
    {
        return $this->render(
            $request->getLocale() == 'de' ? 'item/admin.de.html.twig' : 'item/admin.en.html.twig', [
                'items' => $this->getDoctrine()->getRepository('AppBundle:Item')->findAll()
            ]
        );
    }

    /**
     * @Route("/{_locale}/admin/item/add", requirements={"_locale" = "en|de"}, name="item_add")
     * @Method("POST")
     */
    public function addAction(Request $request)
    {
        $date = new \DateTime();
        $date->setDate(2016, 10, 23);
        $date->setTime(0, 0, 0);
        $item = new Item();
        $item
            ->setName('Nail varnish')
            ->setDescription('')
            ->setDue($date)
            ->setUrl('')
            ->setContributor($this->getDoctrine()->getRepository('AppBundle:User')->find(1));
        $em = $this->getDoctrine()->getManager();
        $em->persist($item);
        $em->flush();

        exit;
    }

    /**
     * @Route("/{_locale}/admin/item/edit", requirements={"_locale" = "en|de"}, name="item_edit")
     * @Method("POST")
     */
    public function editAction(Request $request)
    {

    }

    /**
     * @Route("/{_locale}/admin/item/delete", requirements={"_locale" = "en|de"}, name="item_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request)
    {
        $token = $request->request->get('_csrf_token');
        $csrfToken = new CsrfToken('administrate-items', $token);
        if (!$this->isCsrfTokenValid('administrate-items', $csrfToken)) {
            $this->addFlash('error', 'Invalid request, please try again.');
            return $this->redirectToRoute('item_list');
        }

        try {
            $id = $request->get('current-data-id');
            Assertion::integerish($id);
        } catch (AssertionFailedException $e) {
            $this->addFlash('error', 'Invalid item, please try again.');
            return $this->redirectToRoute('item_list');
        }

        $item = $this->getDoctrine()->getRepository('AppBundle:Item')->find($id);
        if (is_null($item)) {
            $this->addFlash('error', 'Could not find item, please try again.');
            return $this->redirectToRoute('item_list');
        }

        $this->getDoctrine()->getManager()->remove($item);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('item_list');
    }
}
