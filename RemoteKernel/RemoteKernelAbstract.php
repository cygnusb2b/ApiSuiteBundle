<?php
namespace Cygnus\ApiSuiteBundle\RemoteKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;

abstract class RemoteKernelAbstract implements RemoteKernelInterface
{
    protected $client;

    final public function getClient()
    {
        return $this->client;
    }

    final public function createSimpleRequest($uri, $method = 'GET', $parameters = array(), $content = null)
    {
        return $this->createRequest($uri, $method, $parameters, array(), array(), array(), $content);
    }

    final public function createRequest($uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server = array(), $content = null)
    {
        // $this->transformFiles($files);
        $request =  Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);

        // Strip default headers to ensure the request is pure.
        foreach ($request->headers->all() as $key => $value) {
            if ('host' === $key) {
                continue;
            }
            $request->headers->remove($key);
        }
        return $request;
    }

    /**
     * Transforms an array of files into an array of Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @param  array $files The files to transform into
     * @return array
     */
    final protected function transformFiles(&$files)
    {
        // ... transform?
    }
}
