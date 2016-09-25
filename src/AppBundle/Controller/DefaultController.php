<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/{_locale}", requirements={"_locale" = "en|de"}, name="homepage")
     */
    public function indexAction(Request $request)
    {
        $view = $request->getLocale() == 'de' ? ':default:index.de.html.twig' : ':default:index.en.html.twig';
        $itemRepository = $this->getDoctrine()->getRepository('AppBundle:Item');

        return $this->render($view, [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
            'items' => $itemRepository->findAllWithNoContributor()
        ]);
    }

    /**
     * @Route("/", defaults={"_locale" = "en"})
     */
    public function redirectAction(Request $request)
    {
        return $this->redirectToRoute('homepage', [], 301);
    }
}
