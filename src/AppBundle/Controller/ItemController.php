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
use AppBundle\Repository\UserRepository;
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
     * @Route("/{_locale}/item/list/{filter}/{order}", requirements={"_locale" = "en|de", "filter" = "none|missing", "order" = "name-asc|name-desc|due-asc|due-desc"}, name="missing_items")
     * @Method("GET")
     */
    public function indexAction(Request $request, $filter = 'none', $order = 'name-asc')
    {
        $user = $this->getUser();
        if (!$user) {
            $request->getSession()->set('_security.main.target_path', $this->generateUrl('missing_items'));
        }

        switch ($filter) {
            case 'missing':
                $items = $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor($order);
                $filterLinks = sprintf(
                    '<a href="%s">Show all items</a>',
                    $this->generateUrl('missing_items', ['filter' => 'none', 'order' => $order])
                );
                break;
            case 'none':
            default:
                $items = $this->getDoctrine()->getRepository('AppBundle:Item')->findAll($order);
                $filterLinks = sprintf(
                    '<a href="%s">Filter pledged items</a>',
                    $this->generateUrl('missing_items', ['filter' => 'missing', 'order' => $order])
                );
                break;
        }

        $orderLinks = 'Order by: ';
        switch ($order) {
            case 'name-desc':
                $orderLinks .= sprintf(
                    '<a href="%s">Name (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-asc'])
                );
                $orderLinks .= '<em>Name (descending)</em> | ';
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-asc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (descending)</a>',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-desc'])
                );
                break;
            case 'due-asc':
                $orderLinks .= sprintf(
                    '<a href="%s">Name (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-asc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Name (descending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-desc'])
                );
                $orderLinks .= '<em>Due Date (ascending)</em> | ';
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (descending)</a>',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-desc'])
                );
                break;
            case 'due-desc':
                $orderLinks .= sprintf(
                    '<a href="%s">Name (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-asc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Name (descending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-desc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-asc'])
                );
                $orderLinks .= '<em>Due Date (descending)</em>';
                break;
            case 'name-asc':
            default:
                $orderLinks .= '<em>Name (ascending)</em> | ';
                $orderLinks .= sprintf(
                    '<a href="%s">Name (descending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'name-desc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (ascending)</a> | ',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-asc'])
                );
                $orderLinks .= sprintf(
                    '<a href="%s">Due Date (descending)</a>',
                    $this->generateUrl('missing_items', ['filter' => $filter, 'order' => 'due-desc'])
                );
                break;
        }

        return $this->render(
            $request->getLocale() == 'de' ? 'item/index.de.html.twig' : 'item/index.en.html.twig', [
                'items' => $items,
                'user' => $user,
                'address_confirmed' => (bool)$request->getSession()->get('address_confirmed', false),
                'filter' => $filterLinks,
                'order' => $orderLinks,
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
        $users = $this->getDoctrine()->getRepository('AppBundle:User')->findAll();

        return $this->render(
            $request->getLocale() == 'de' ? 'item/add.de.html.twig' : 'item/add.en.html.twig', [
                'users' => $users
            ]
        );
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
        $csrfToken = new CsrfToken('add-item', $token);
        if (!$this->isCsrfTokenValid('add-item', $csrfToken)) {
            $this->addFlash('error', 'Invalid request, please try again.');
            return $this->redirectToRoute('item_list');
        }

        $inputDataErrors = $this->getInputDataErrors($request);
        if (!empty($inputDataErrors)) {
            foreach ($inputDataErrors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('item_list');
        }

        $item = new Item();

        $item->setName($request->get('item-name'));

        if ($item->getDescription() != $request->get('item-description')) {
            $item->setDescription($request->get('item-description'));
        }

        if ($item->getUrl() != $request->get('item-url')) {
            $item->setUrl($request->get('item-url'));
        }

        if (!$request->get('item-due-date-none')) {
            $this->setDueDate($item, $request);
        }

        if ($request->get('item-contributor')) {
            $user = $this->getDoctrine()->getRepository('AppBundle:User')->find($request->get('item-contributor'));
            if (is_null($user)) {
                $this->addFlash(
                    'error',
                    sprintf(
                        'Could not find contributor user ID: %d. Please try again',
                        $request->get('item-contributor')
                    )
                );
                return $this->redirectToRoute('item_list');
            }
            $item->setContributor($user);
        }

        $this->getDoctrine()->getManager()->persist($item);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('item_list');
    }

    /**
     * @Route("/{_locale}/admin/item/update", requirements={"_locale" = "en|de"}, name="item_update")
     * @Method("POST")
     */
    public function updateAction(Request $request)
    {
        $disabledChecks = [];
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
        } else {
            $disabledChecks[] = 'name';
        }

        if ($item->getDescription() != $request->get('item-description')) {
            $item->setDescription($request->get('item-description'));
        }

        if ($item->getUrl() != $request->get('item-url')) {
            $item->setUrl($request->get('item-url'));
        } else {
            $disabledChecks[] = 'url';
        }

        if (!$request->get('item-due-date-none')) {
            $hasChanged = $this->setDueDate($item, $request);
            if (!$hasChanged) {
                $disabledChecks[] = 'due-date';
            }
        } else {
            $this->unsetDueDate($item);
            $disabledChecks[] = 'due-date';
        }

        $inputDataErrors = $this->getInputDataErrors($request, $disabledChecks);
        if (!empty($inputDataErrors)) {
            foreach ($inputDataErrors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('item_list');
        }

        $this->updateContributor($item, $request);

        $this->getDoctrine()->getManager()->persist($item);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('item_list');
    }

    /**
     * Returns true if date check is required.
     *
     * @param Item &$item
     * @param Request $request
     * @return bool
     */
    private function setDueDate(Item &$item, Request $request) {
        if ('de' == $request->getLocale()) {
            $format = 'd.m.Y';
        } else {
            $format = 'm/d/Y';
        }
        $inputDate = DateTime::createFromFormat($format, $request->get('item-due-date'));
        $itemDueDate = $item->getDue();
        if (!is_null($itemDueDate) && $itemDueDate->format($format) == $inputDate->format($format)) {
            return false;
        }
        $item->setDue($inputDate);

        return true;
    }

    /**
     * @param Item &$item
     */
    private function unsetDueDate(Item &$item) {
        if (!$item->getDue()) {
            return;
        }

        $item->setDue(null);
    }

    /**
     * @param Item &$item
     * @param Request $request
     */
    private function updateContributor(Item &$item, Request $request)
    {
        $contributorInput = $request->get('item-contributor');

        if (0 == $contributorInput && $item->getContributor()) {
            $item->setContributor(null);
            return;
        }

        $foundContributorUserObj = $this->getDoctrine()->getRepository('AppBundle:User')->find($contributorInput);
        if (!$foundContributorUserObj) {
            return;
        }


        if (!$item->getContributor()) {
            $item->setContributor($foundContributorUserObj);
            return;
        }

        if ($item->getContributor()->getId() == $contributorInput) {
            return;
        }

        $item->setContributor($foundContributorUserObj);
    }

    /**
     * @param Request $request
     * @param string[] $disabledChecks
     * @return string[]
     */
    private function getInputDataErrors(Request $request, $disabledChecks = [])
    {
        $errors = [];

        if (!in_array('name', $disabledChecks)) {
            $itemName = $request->get('item-name');
            if (!strlen($itemName))
            {
                $errors[] = 'Item name must be set';
            }
            if (strlen($itemName) < 2)
            {
                $errors[] = 'Item name must be at least 2 characters.';
            }
            if (strlen($itemName) > 255)
            {
                $errors[] = 'Item name must not be longer than 255 characters.';
            }
        }
        if (!in_array('url', $disabledChecks)) {
            $itemUrl = $request->get('item-url');
            if (strlen($itemUrl) && filter_var($itemUrl, FILTER_VALIDATE_URL) === false)
            {
                $errors[] = sprintf('"%s" is not a valid URL.', $itemUrl);
            }
        }

        if (!in_array('due-date', $disabledChecks)) {
            $itemDueDate = $request->get('item-due-date-none') ? '' : DateTime::createFromFormat('m/d/Y', $request->get('item-due-date'));
            if (is_object($itemDueDate) && $itemDueDate->getTimestamp() < time())
            {
                $errors[] = 'The due date must lie in the future.';
            }
        }

        return $errors;
    }
}
