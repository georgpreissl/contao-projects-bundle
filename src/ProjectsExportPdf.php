<?php

/**
 * Space Icon
 * 
 * @copyright  Georg Preissl 2017
 * @package    spaceicon
 * @author     Georg Preissl <http://www.georg-preissl.at>
 * @license    LGPL
 */

namespace GeorgPreissl\Projects;


class ProjectsExportPdf extends \Backend {

    /**
     * Change database margin value
     */
    public function executePreActions($strAction)
    {
        if ($strAction == 'changeSpace')
        {
            $id = \Input::post('id');
            $strMinus = \Input::post('minus');
            $isMinus = ($strMinus === 'true' ? true : false);

            $this->import('Database');
            $result = $this->Database->prepare("SELECT space FROM tl_content WHERE id=?")->execute($id);
            $arrSpace = deserialize($result->space);

            if ($isMinus) {
                if ($arrSpace[1]-10 >= 0) {
                    $arrSpace[1] = $arrSpace[1]-10;
                }
            } else {
                $arrSpace[1] = $arrSpace[1]+10;
            }
            
            $this->Database->prepare("UPDATE tl_content SET space = ? WHERE id=?")->execute(serialize($arrSpace), $id);
            echo $arrSpace[1] . 'px';
            exit; 
        }        
    }
    




    /**
     * Create the html for the icon
     */
    public function createExportIcon($row, $href, $label, $title, $icon, $attributes)
    {
        // $result = $this->Database->prepare("SELECT space FROM tl_content WHERE id=?")->execute($row['id']);
        // $arrSpace = deserialize($result->space);

        // if ($arrSpace[1]=='') {
        //     $arrSpace[1]=0;
        // }

        return '<a class="space_iconx" href="contao/main.php?do=projectspdfexport&id='.$row['id'].'" title="'.specialchars($title).'"'.$attributes.'>'
        .'<img src="bundles/georgpreisslprojects/export-pdf.png" >'
        .'</a> ';
    }

}  




?>