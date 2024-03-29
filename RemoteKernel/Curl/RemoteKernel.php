<?php
namespace Cygnus\ApiSuiteBundle\RemoteKernel\Curl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;

class RemoteKernel extends RemoteKernelAbstract
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = RemoteKernelInterface::MASTER_REQUEST, $catch = true)
    {
        try {
            return $this->handleRaw($request, $type);
        } catch (\Exception $e) {
            if (false === $catch) {
                $this->finishRequest($request, $type);
                throw $e;
            }

            return $this->handleException($e, $request, $type);
        }
    }

    /**
     * Handles a request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @param Request $request A Request instance
     * @param integer $type    The type of the request (one of HtstpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response A Response instance
     */
    private function handleRaw(Request $request, $type = RemoteKernelInterface::MASTER_REQUEST)
    {
        $this->client->setRequest($request);
        return $this->client->execute();
    }

    /**
     * Handles an exception by trying to convert it to a Response.
     *
     * @param \Exception $e       An \Exception instance
     * @param Request    $request A Request instance
     * @param integer    $type    The type of the request
     *
     * @return Response A Response instance
     *
     * @throws \Exception
     */
    private function handleException(\Exception $e, Request $request, $type)
    {
        return new Response(
            $e->getMessage(),
            500
        );
    }
}
