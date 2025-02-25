<?php

declare(strict_types=1);



namespace GeorgPreissl\Projects;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GeorgPreisslProjectsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }  
}

