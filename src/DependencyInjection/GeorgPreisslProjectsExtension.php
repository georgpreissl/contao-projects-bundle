<?php



namespace GeorgPreissl\Projects\DependencyInjection;

// use Codefog\NewsCategoriesBundle\Migration\BooleanFieldsMigration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GeorgPreisslProjectsExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        
        $loader->load('listener.yml');
        $loader->load('services.yml');



        // Remove migration service for Contao 4.4
        // if (!class_exists(AbstractMigration::class)) {
        //     $container->removeDefinition(BooleanFieldsMigration::class);
        // }
    }
}



