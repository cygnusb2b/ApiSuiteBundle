<?php
namespace Cygnus\ApiSuiteBundle\RemoteKernel\Curl\Processor;

interface ProcessorInterface
{
    public function process();
    public function get();
    public function reset();
}
