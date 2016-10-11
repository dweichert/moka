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
use DateTime;
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
     * @Method("GET")
     */
    public function addAction(Request $request)
    {
        $date = new DateTime();
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
     * @Route("/{_locale}/admin/item/{id}/edit", requirements={"_locale" = "en|de", "id" = "\d+"}, name="item_edit")
     * @Method("GET")
     */
    public function editAction(Request $request, $id)
    {
        $item = $this->getDoctrine()->getRepository('AppBundle:Item')->find($id);
        $users = $this->getDoctrine()->getRepository('AppBundle:User')->findAll();

        return $this->render(
            $request->getLocale() == 'de' ? 'item/edit.de.html.twig' : 'item/edit.en.html.twig', [
                'item' => $item,
                'users' => $users
            ]
        );
    }

    /**
     * @Route("/{_locale}/admin/item/delete", requirements={"_locale" = "en|de"}, name="item_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request)
    {
        $token = $request->get('_csrf_token');
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

    /**
     * @Route("/{_locale}/admin/item/save", requirements={"_locale" = "en|de"}, name="item_save")
     * @Method("POST")
     */
    public function saveAction(Request $request)
    {
        $token = $request->get('_csrf_token');
        $csrfToken = new CsrfToken('edit-item', $token);
        if (!$this->isCsrfTokenValid('edit-item', $csrfToken)) {
            $this->addFlash('error', 'Invalid request, please try again.');
            return $this->redirectToRoute('item_list');
        }

        try {
            $id = $request->get('item-id');
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

        if ($item->getName() != $request->get('item-name')) {
            $item->setName($request->get('item-name'));
        }

        if ($item->getDescription() != $request->get('item-description')) {
            $item->setDescription($request->get('item-description'));
        }

        if ($item->getUrl() != $request->get('item-url')) {
            $item->setUrl($request->get('item-url'));
        }

        if ($request->get('item-due-date-none')) {
            $this->setDueDate($item, $request);
        } else {
            $this->unsetDueDate($item);
        }

        if ($this->canSetContributor($item, $request->get('item-contributor'))) {
            $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($request->get('item-contributor'));
            if (is_null($user)) {
                $this->addFlash(
                    'error',
                    sprintf(
                        'Could not find contributor user ID: %d. Please try again',
                        $request->get('item-contributor')
                    )
                );
                $this->redirectToRoute('item_list');
            }
            $item->setContributor($user);
        }

        $this->getDoctrine()->getManager()->persist($item);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('item_list');
    }

    /**
     * @param Item $item
     * @param Request $request
     */
    private function setDueDate(Item $item, Request $request) {
        $inputDate = DateTime::createFromFormat('d/m/Y', $request->get('item-due-date'));;

        if ($item->getDue() == $inputDate) {
            return;
        }

        $item->setDue($inputDate);
    }

    /**
     * @param Item $item
     */
    private function unsetDueDate(Item $item) {
        if (!$item->getDue()) {
            return;
        }

        $item->setDue(null);
    }

    /**
     * Can the contributor be set?
     *
     * @param Item $item
     * @param string $contributorInput
     * @return bool
     */
    private function canSetContributor(Item $item, $contributorInput)
    {
        if (1 > $contributorInput) {
            return false;
        }
        if (!$item->getContributor()) {
            return true;
        }
        if (!$item->getContributor()->getId() == $contributorInput) {
            return false;
        }

        return true;
    }
}
