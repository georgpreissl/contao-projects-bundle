<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace GeorgPreissl\Projects;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GeorgPreisslProjectsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }  
}

