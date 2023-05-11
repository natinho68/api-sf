<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VersioningService
{
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->defaultVersion = $params->get('default_api_version');
    }

    public function getVersion(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $accept = $request->headers->get('Accept');
        $version = $this->defaultVersion;

        if ($accept) {
            $matches = [];
            preg_match('/version=([^;]+)/i', $accept, $matches);

            if (isset($matches[1])) {
                $version = $matches[1];
            }
        }

        return $version;
    }
}
