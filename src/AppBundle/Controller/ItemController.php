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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ItemController extends Controller
{
    /**
     * @Route("/{_locale}/item/add", requirements={"_locale" = "en|de"}, name="item_add")
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
     * @Route("/{_locale}/item/edit", requirements={"_locale" = "en|de"}, name="item_edit")
     */
    public function editAction(Request $request)
    {

    }

    /**
     * @Route("/{_locale}/item/delete", requirements={"_locale" = "en|de"}, name="item_delete")
     */
    public function deleteAction(Request $request)
    {

    }
}
