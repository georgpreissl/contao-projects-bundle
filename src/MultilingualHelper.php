<?php

/*
 * News Categories bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, Codefog
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace GeorgPreissl\Projects;

use Contao\System;

class MultilingualHelper
{
    /**
     * Return true if the multilingual features are active.
     *
     * @return bool
     */
    public static function isActive()
    {
        return \array_key_exists('Terminal42DcMultilingualBundle', System::getContainer()->getParameter('kernel.bundles'));
    }
}
