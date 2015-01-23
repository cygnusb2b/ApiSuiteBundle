<?php

namespace Cygnus\ApiSuiteBundle;

use Cygnus\ApiSuiteBundle\DependencyInjection\Compiler\CacheClientCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CygnusApiSuiteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Register cacheable clients
        $container->addCompilerPass(new CacheClientCompilerPass());
    }
}
