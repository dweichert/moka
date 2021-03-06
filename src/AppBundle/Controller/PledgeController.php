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
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

class PledgeController extends Controller
{
    /**
     * @Route("/{_locale}/pledge", requirements={"_locale" = "en|de"}, name="pledge_list")
     * @Method("GET")
     */
    public function listAction(Request $request)
    {
        /** @var User $contributor */
        $contributor = $this->getUser();
        if (!$contributor) {
            $this->addFlash('error', 'Could not determine user data, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        $view = $request->getLocale() == 'de' ? ':pledge:list.de.html.twig' : ':pledge:list.en.html.twig';

        return $this->render($view, [
            'items' => $contributor->getItems()->toArray(),
        ]);
    }

    /**
     * @Route("/{_locale}/pledge/cancel", requirements={"_locale" = "en|de"}, name="cancel_pledge")
     * @Method("POST")
     */
    public function cancelAction(Request $request)
    {
        $token = $request->get('_csrf_token');
        $csrfToken = new CsrfToken('cancel_item', $token);
        if (!$this->isCsrfTokenValid('cancel_item', $csrfToken)) {
            $this->addFlash('error', 'Invalid request, please try again.');
            return $this->redirectToRoute('pledge_list');
        }

        try {
            $id = $request->get('cancelled-item');
            Assertion::integerish($id);
        } catch (AssertionFailedException $e) {
            $this->addFlash('error', 'Invalid item, please try again.');
            return $this->redirectToRoute('pledge_list');
        }

        $item = $this->getDoctrine()->getRepository('AppBundle:Item')->find($id);
        if (is_null($item)) {
            $this->addFlash('error', 'Could not find pledged item, please try again.');
            return $this->redirectToRoute('pledge_list');
        }

        $item->setContributor(null);
        $em = $this->getDoctrine()->getManager();
        $em->persist($item);
        $em->flush();
        $this->removeExcessUnpledgedItems($item->getClass());

        return $this->redirectToRoute('pledge_list');
    }

    /**
     * @Route("/{_locale}/pledge", requirements={"_locale" = "en|de"}, name="pledge")
     * @Method("POST")
     */
    public function indexAction(Request $request)
    {
        $token = $request->get('_csrf_token');
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
        if (is_null($item)) {
            $this->addFlash('error', 'Could not find pledged item, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        if ($item->getContributor()) {
            $this->addFlash('error', 'Item has already been pledged by someone else. Please choose another item.');
            return $this->redirectToRoute('missing_items');
        }

        /** @var User $contributor */
        $contributor = $this->getUser();
        if (!$contributor) {
            $this->addFlash('error', 'Could not determine user data, please try again.');
            return $this->redirectToRoute('missing_items');
        }

        $request->getSession()->set('address_confirmed', true);

        $objectManager = $this->getDoctrine()->getManager();
        $this->updateAddressData($contributor, $request, $objectManager);
        $item->setContributor($contributor);
        $objectManager->persist($item);
        $objectManager->flush();

        $view = $request->getLocale() == 'de' ? ':pledge:success.de.html.twig' : ':pledge:success.en.html.twig';

        return $this->render($view, [
            'item' => $item,
            'items' => $contributor->getItems()->toArray(),
        ]);
    }

    private function updateAddressData(User $user, Request $request, ObjectManager $objectManager)
    {
        $changed = false;
        if ($user->getStreetAddress() != $request->get('user-street-address-1')) {
            $user->setStreetAddress($request->get('user-street-address-1'));
            $changed = true;
        }
        if ($user->getStreetAddress2() != $request->get('user-street-address-2')) {
            $user->setStreetAddress2($request->get('user-street-address-2'));
            $changed = true;
        }
        if ($user->getPostalCode() != $request->get('user-postal-code')) {
            $user->setPostalCode($request->get('user-postal-code'));
            $changed = true;
        }
        if ($user->getCity() != $request->get('user-city')) {
            $user->setCity($request->get('user-city'));
            $changed = true;
        }
        if ($user->getCountry() != $request->get('user-country')) {
            $user->setCountry($request->get('user-country'));
            $changed = true;
        }
        if ($user->getPhone() != $request->get('user-phone')) {
            $user->setPhone($request->get('user-phone'));
            $changed = true;
        }
        if ($user->getMobile() != $request->get('user-mobile')) {
            $user->setMobile($request->get('user-mobile'));
            $changed = true;
        }
        if ($changed) {
            $objectManager->persist($user);
        }
    }

    /**
     * Remove unpledged items of given class in excess of one.
     *
     * @param string $itemClass
     */
    private function removeExcessUnpledgedItems($itemClass)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Item[] $itemsOfSameClass */
        $itemsOfSameClass = $this->getDoctrine()->getRepository('AppBundle:Item')->findBy(['class' => $itemClass]);
        $unpledged = 0;
        foreach ($itemsOfSameClass as $itemOfSameClass) {
            if (!$itemOfSameClass->getContributor()) {
                $unpledged++;
            }
            if ($unpledged > 1) {
                $em->remove($itemOfSameClass);
            }
        };
        $em->flush();
    }
}
