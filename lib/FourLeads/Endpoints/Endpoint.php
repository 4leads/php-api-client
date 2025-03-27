<?php

namespace FourLeads\Endpoints;

use FourLeads\FourLeadsAPI;
use FourLeads\FourLeadsResponse;

class Endpoint
{
    protected FourLeadsAPI $client;

    public function __construct(FourLeadsAPI $client)
    {
        $this->client = $client;
    }

    protected function buildUrl($path, $queryParams = null): string
    {
        return $this->client->buildUrl($path, $queryParams);
    }

    protected function makeRequest($method, $url, $body = null, $headers = null): FourLeadsResponse
    {
        return $this->client->makeRequest($method, $url, $body, $headers);
    }
}