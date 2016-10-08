<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/{_locale}", requirements={"_locale" = "en|de"}, name="homepage")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $view = $request->getLocale() == 'de' ? ':default:index.de.html.twig' : ':default:index.en.html.twig';
        $itemRepository = $this->getDoctrine()->getRepository('AppBundle:Item');

        $percent = (int) round(100 / $itemRepository->getAllItemsCount() * $itemRepository->getPledgedItemsCount());
        if ($percent < 33) {
            $status = 'danger';
        } elseif ($percent < 66) {
            $status = 'warning';
        } else {
            $status = 'success';
        }
        return $this->render($view, [
            'percent' => $percent,
            'status' => $status
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
