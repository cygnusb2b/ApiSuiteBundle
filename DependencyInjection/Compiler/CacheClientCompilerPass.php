<?php
namespace Cygnus\ApiSuiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CacheClientCompilerPass implements CompilerPassInterface
{
    /**
     * The tag name used for API resources
     */
    const TAG_NAME = 'cygnus_api_suite.cacheable';

    /**
     * Adds tagged cacheable clients
     *
     * @param   Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return  void
     */
    public function process(ContainerBuilder $container)
    {
        // Get the tagged resources
        $resources = $container->findTaggedServiceIds(static::TAG_NAME);

        $cacheClientId = $container->getParameter('cygnus_api_suite.cache_client');

        foreach ($resources as $id => $tagAttributes) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall(
                'setCacheClient',
                [new Reference($cacheClientId)]
            );
        }
    }
}
