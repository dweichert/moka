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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

class ItemController extends Controller
{
    const FILTER_NONE = 'none';
    const FILTER_MISSING_ITEMS = 'missing';
    const ORDER_WEIGHT_ASC = 'weight-asc';
    const ORDER_NAME_ASC = 'name-asc';
    const ORDER_NAME_DESC = 'name-desc';
    const ORDER_DUE_DATE_ASC = 'due-asc';
    const ORDER_DUE_DATE_DESC = 'due-desc';

    /**
     * @var Item[]
     */
    private $items;

    private $translations = [
        'Show All' => 'Alle anzeigen',
        'Filter Pledged' => 'Nur noch nicht gespendete',
        'Weight (ascending)' => 'Gewicht (aufsteigend)',
        'Name (ascending)' => 'Name (aufsteigend)',
        'Name (descending)' => 'Name (absteigend)',
        'Required by (ascending)' => 'Benötigt bis (aufsteigend)',
        'Required by (descending)' => 'Benötigt bis (absteigend)',
    ];

    /**
     * Item list, aka "Pledge" list.
     *
     * List shown to users to allow them to pledge items.
     *
     * @Route("/{_locale}/item/list/{filter}/{order}", requirements={"_locale" = "en|de", "filter" = "none|missing", "order" = "weight-asc|name-asc|name-desc|due-asc|due-desc"}, name="missing_items")
     * @Method("GET")
     *
     * @param Request $request
     * @param string $filter
     * @param string $order
     * @return Response
     */
    public function indexAction(Request $request, $filter = 'none', $order = 'weight-asc')
    {
        $user = $this->getUser();
        if (!$user) {
            $request->getSession()->set('_security.main.target_path', $this->generateUrl('missing_items'));
        }

        $locale = $request->getLocale();

        return $this->render(
            $locale == 'de' ? 'item/index.de.html.twig' : 'item/index.en.html.twig', [
                'items' => $this->getItems($filter, $order),
                'user' => $user,
                'address_confirmed' => (bool)$request->getSession()->get('address_confirmed', false),
                'filter' => $this->getFilterOptions($filter, $locale),
                'order' => $this->getOrderOptions($order, $locale),
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

        $item->setDescription($request->get('item-description', ''));

        $item->setUrl($request->get('item-url', ''));

        if (!$request->get('item-due-date-none')) {
            $this->setDueDate($item, $request);
        }

        $item->setWeight($request->get('item-weight'));

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

        if ($item->getWeight() != $request->get('item-weight')) {
            $item->setWeight($request->get('item-weight'));
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

    /**
     * Get items.
     *
     * @param string $filter
     * @param string $order
     *
     * @return Item[]
     */
    private function getItems($filter, $order)
    {
        if (!is_null($this->items)) {
            return $this->items;
        }

        switch ($filter) {
            case self::FILTER_MISSING_ITEMS:
                $this->items = $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor($order);

                return $this->items;
            case self::FILTER_NONE:
            default:
                $this->items = $this->getDoctrine()->getRepository('AppBundle:Item')->findAll($order);

                return $this->items;
        }
    }

    /**
     * Get item list filter options.
     *
     * @param string $filter
     * @return string
     */
    private function getFilterOptions($filter, $locale)
    {
        return sprintf(
            '<option value="%1$s"%2$s>'
            . $this->getLabel('Show All', $locale)
            . '</option>'
            . '<option value="%3$s"%4$s>'
            . $this->getLabel('Filter Pledged', $locale)
            . '</option>',
            self::FILTER_NONE,
            $filter == self::FILTER_NONE ?  ' selected="selected"' : '',
            self::FILTER_MISSING_ITEMS,
            $filter == self::FILTER_MISSING_ITEMS ?  ' selected="selected"' : ''
        );
    }

    /**
     * Get item list order options.
     *
     * @param $order
     * @param $locale
     * @return string
     */
    private function getOrderOptions($order, $locale)
    {
        return sprintf(
            '<option value="%1$s"%2$s>'
            . $this->getLabel('Weight (ascending)', $locale)
            . '</option>'
            . '<option value="%3$s"%4$s>'
            . $this->getLabel('Name (ascending)', $locale)
            . '</option>'
            . '<option value="%5$s"%6$s>'
            . $this->getLabel('Name (descending)', $locale)
            . '</option>'
            . '<option value="%7$s"%8$s>'
            . $this->getLabel('Required by (ascending)', $locale)
            . '</option>'
            . '<option value="%9$s"%10$s>'
            . $this->getLabel('Required by (descending)', $locale)
            . '</option>',
            self::ORDER_WEIGHT_ASC,
            $order == self::ORDER_WEIGHT_ASC ? 'selected="selected"' : '',
            self::ORDER_NAME_ASC,
            $order == self::ORDER_NAME_ASC ? ' selected="selected"' : '',
            self::ORDER_NAME_DESC,
            $order == self::ORDER_NAME_DESC ? ' selected="selected"' : '',
            self::ORDER_DUE_DATE_ASC,
            $order == self::ORDER_DUE_DATE_ASC ? ' selected="selected"' : '',
            self::ORDER_DUE_DATE_DESC,
            $order == self::ORDER_DUE_DATE_DESC ? ' selected="selected"' : ''
        );
    }


    /**
     * Get translated label.
     *
     * @param string $string
     * @param string $locale
     * @return string
     */
    private function getLabel($string, $locale) {
        if ('de' == $locale && isset($this->translations[$string])) {

            return $this->translations[$string];
        }

        return $string;
    }
}
