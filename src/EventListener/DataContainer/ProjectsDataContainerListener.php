<?php

declare(strict_types=1);



namespace GeorgPreissl\Projects\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Component\HttpFoundation\RequestStack;


class ProjectsDataContainerListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(): void
    {
        
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $sorting = $session->getBag('contao_backend')->get('sorting')['tl_projects'] ?? null;

        /*
            '$sorting' is the field from tl_project which has been choosen by the user in the backend for sorting.
            It can have one of these values: 
            "date DESC" (Datum)
            "customer" (Kunde)
            "location" (Ort)
            "headline" (Title)
            "sorting" (Sortierindex)
        */

        // Only set sorting as the first field if custom sorting is chosen.
        if ('sorting' === $sorting) {
            // $GLOBALS['TL_DCA']['tl_projects']['list']['sorting']['fields'] = ['sorting', 'date'];
            $GLOBALS['TL_DCA']['tl_projects']['list']['sorting']['fields'] = ['sorting'];
        }
    }

  
}
