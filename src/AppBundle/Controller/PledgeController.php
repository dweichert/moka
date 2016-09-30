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

class PledgeController extends Controller
{
    /**
     * @Route("/{_locale}/pledge", requirements={"_locale" = "en|de"}, name="missing_items")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render(
            $request->getLocale() == 'de' ? 'pledge/index.de.html.twig' : 'pledge/index.en.html.twig', [
                'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..'),
                'items' => $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor(),
            ]
        );
    }

    /**
     * @Route("/{_locale}/pledge/{id}", requirements={"_locale" = "en|de", "id" = "\d+"}, name="pledge")
     * @Method("POST")
     */
    public function pledgeAction($id, Request $request)
    {
        $item = $this->getDoctrine()->getRepository('AppBundle:Item')->find($id);
        if (is_null($item))
        {
            $view = $request->getLocale() == 'de' ? ':pledge:notfound.de.html.twig' : ':pledge:notfound.en.html.twig';
            return $this->render($view, [
                'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..'),
                'items' => $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor(),
            ]);
        }
        if ($item->getContributor())
        {
            $view = $request->getLocale() == 'de' ? ':pledge:forbidden.de.html.twig' : ':pledge:forbidden.en.html.twig';
            return $this->render($view, [
                'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..'),
                'items' => $this->getDoctrine()->getRepository('AppBundle:Item')->findAllWithNoContributor(),
            ]);
        }
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
