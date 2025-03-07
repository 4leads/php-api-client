<?php

namespace FourLeads\Endpoints;

use FourLeads\FourLeadsAPI;
use stdClass;

class Storage
{
    private FourLeadsAPI $client;

    public function __construct(FourLeadsAPI $client)
    {
        $this->client = $client;
    }

    /**
     * Returns a list of all global values
     * @return stdClass
     */
    public function getGlobalValuesList(): stdClass
    {
        $path = '/storage';
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('GET', $url);
    }

    /**
     * Returns a single global value based on its specific key
     * @param string $key The unique internal identifier of the global value
     * @return stdClass
     */
    public function getGlobalValue(string $key): stdClass
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('GET', $url);
    }

    /**
     * Retrieves the value associated with a specific key. If no key is provided, returns a list of results
     * @param string|null $key The unique internal identifier of the global value, or null to fetch all available values.
     * @return stdClass
     */
    public function getGlobalValueValue(?string $key = null): stdClass
    {
        $path = '/storage-values' . ($key ? '/' . urlencode($key) : '');
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('GET', $url);
    }

    /**
     * Sets the value for a specific key
     * @param string $key
     * @param string $value
     * @param bool $overwrite
     * @return stdClass
     */
    public function setGlobalValueValue(string $key, string $value, bool $overwrite): stdClass
    {
        $path = '/storage-values';
        $url = $this->client->buildUrl($path);

        $data = new stdClass();
        $data->fields = [
            (object)[
                'key' => $key,
                'value' => $value
            ]
        ];

        $data->options = new stdClass();
        $data->options->overwrite = $overwrite;

        return $this->client->makeRequest('POST', $url, $data);
    }

    /**
     * Creates a global value
     * @param stdClass $globalValue An object containing the following properties:
     *  - string $name The descriptive name of the global value
     *  - string $typeId The associated type identifier (refer to the Global Values class for valid type IDs)
     *  - string $key The internal unique identifier for this field
     *  - string $value The assigned value of the field
     * @return stdClass
     */
    public function createGlobalValue(\stdClass $globalValue): stdClass
    {
        $path = '/storage';
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('POST', $url, $globalValue);
    }

    /**
     * Updates a global value for a specific key
     *
     * @param string $key The key of the global value to update.
     * @param stdClass $globalValue An object containing the following properties:
     *   - string $name The descriptive name of the global value.
     *   - string $typeId The associated type identifier (refer to the Global Values class for valid type IDs).
     *   - string $key The internal unique identifier for this field.
     *   - string $value The assigned value of the field.
     *
     * @return stdClass
     */
    public function updateGlobalValue(string $key, \stdClass $globalValue): stdClass
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('PUT', $url, $globalValue);
    }

    /**
     * Deletes a global value for a specific key
     * @param string $key
     * @return stdClass
     */
    public function deleteGlobalValue(string $key): stdClass
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('DELETE', $url);
    }
}