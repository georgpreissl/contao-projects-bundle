<?php

declare(strict_types=1);


namespace GeorgPreissl\Projects\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use GeorgPreissl\Projects\GeorgPreisslProjectsBundle;

/**
 * @internal
 */
class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(GeorgPreisslProjectsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }









}
