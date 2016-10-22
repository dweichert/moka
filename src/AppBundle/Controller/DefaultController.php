<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Item;
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

        return $this->render($view, [
            'puzzle' => $this->solveThePuzzle()
        ]);
    }

    /**
     * @Route("/", defaults={"_locale" = "en"})
     */
    public function redirectAction(Request $request)
    {
        return $this->redirectToRoute('homepage', [], 301);
    }

    /**
     * Generates the puzzle tiles on the home page.
     *
     * @return string
     */
    private function solveThePuzzle()
    {
        $puzzle = [];

        $items = $this->getDoctrine()->getRepository('AppBundle:Item')->findAll();

        $index = 0;
        foreach ($items as $item)
        {
            $contributor = $item->getContributor();
            $puzzle[] = sprintf(
                '%1$s
                <a href="' . $this->generateUrl('missing_items') . '">
                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-3" title="%2$s">
                    <div class="jigsaw-piece %3$s">
                        <span class="jigsaw-piece-t %4$s"></span>
                        <span class="jigsaw-piece-l jigsaw-white"></span>
                        <span class="jigsaw-piece-r %5$s"></span>
                        <span class="jigsaw-piece-b jigsaw-white"></span>
                        <span class="%6$s">%7$s</span>
                    </div>
                </div>
                </a>
                %8$s',
                $index == 0 ? '<div class="row">' : '',
                $this->getJigsawTitle($item),
                $contributor ? 'piece-pledged' : 'piece-missing',
                $contributor ? 'jigsaw-red' : 'jigsaw-grey',
                $contributor ? 'jigsaw-green' : 'jigsaw-grey',
                $contributor ? 'jigsaw-label-pledged' : 'jigsaw-label-missing',
                $this->getJigsawText($item),
                $index == 11 ? '</div>' : ''
            );

            if ($index == 11) {
                $index = 0;
            } else {
                $index++;
            }
        }

        return implode('', $puzzle);
    }

    /**
     * @param Item $item
     * @return string
     */
    private function getJigsawTitle($item)
    {
        if (!$item->getContributor()) {
            return 'Horkus is Waiting…';
        }

        return sprintf(
            'Pledged by %s',
            $this->getContributorName($item)
        );
    }

    /**
     * @param Item $item
     * @return string
     */
    private function getJigsawText($item)
    {
        if (!$item->getContributor()) {
            return '<i class="fa fa-question" aria-hidden="true"></i>';
        }

        return '<i class="fa fa-gift" aria-hidden="true"></i>';
    }

    /**
     * @param Item $item
     * @return string
     */
    private function getContributorName(Item $item)
    {
        if (strlen($item->getContributor()->getFirstName())) {
            return $item->getContributor()->getFirstName();
        }

        return $item->getContributor()->getUsername();
    }
}
