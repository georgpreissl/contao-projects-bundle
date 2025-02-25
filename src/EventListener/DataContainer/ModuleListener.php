<?php

declare(strict_types=1);


namespace GeorgPreissl\Projects\EventListener\DataContainer;

use Contao\DataContainer;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Contao\BackendTemplate;
use Contao\Date;
use Contao\StringUtil;
use Contao\NewsModel;
use Contao\CoreBundle\Exception\PageNotFoundException;
use GeorgPreissl\Projects\Model\ProjectsModel;

#[AsCallback(table: 'tl_module', target: 'config.onload')]
class ModuleListener
{

    protected $bubi = array();

    public function __invoke(DataContainer|null $dc = null): void
    {
        $result = Database::getInstance()
        ->prepare("SELECT type FROM tl_module WHERE id=?")
        ->execute($dc->id);	        
        if($result->type == "projectsreader") {
            $GLOBALS['TL_DCA']['tl_module']['fields']['imgSize']['label'] = array("Bildgröße Hauptbild","Hier können Sie die Abmessungen des Hauptbilds festlegen.");
        };

        // $this->x = Environment::get('requestUri');
        // $m= Input::get('id');

        // $result = Database::getInstance()->prepare("SELECT * FROM tl_log")->limit(30)->execute();
        // dump($m);
        // throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        // $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        // $this->x = System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request);
        // dump($request::get('requestUri'));
        // $objTemplate = new BackendTemplate('be_jobo');
        // $objTemplate->title = 'hello';
        // $x = $objTemplate->parse();
        // $this->bubi[] = new Date();
        // $n = NewsModel::findOneBy('id', 1);
        // dump(ProjectsModel::findPublishedByPids([1]));
        // dump(class_exists(\GeorgPreissl\Projects\Model\ProjectsModel::class));
                           

    }

}


