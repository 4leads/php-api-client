<?php

namespace FourLeads\Endpoints;

use FourLeads\FourLeadsAPI;
use FourLeads\FourLeadsResponse;
use stdClass;

class Storage extends Endpoint
{


    /**
     * Returns a list of all global values
     * @return FourLeadsResponse
     */
    public function list(): FourLeadsResponse
    {
        $path = '/storage';
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Returns a single global value based on its specific key
     * @param string $key The unique internal identifier of the global value
     * @return FourLeadsResponse
     */
    public function get(string $key): FourLeadsResponse
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Retrieves the value associated with a specific key. If no key is provided, returns a list of results
     * @param string|null $key The unique internal identifier of the global value, or null to fetch all available values.
     * @return FourLeadsResponse
     */
    public function getValue(?string $key = null): FourLeadsResponse
    {
        $path = '/storage-values' . ($key ? '/' . urlencode($key) : '');
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Sets the value for a specific key
     * @param string $key
     * @param string $value
     * @param bool $overwrite
     * @return FourLeadsResponse
     */
    public function setValue(string $key, string $value, bool $overwrite): FourLeadsResponse
    {
        $path = '/storage-values';
        $url = $this->buildUrl($path);

        $data = new stdClass();
        $data->fields = [
            (object)[
                'key' => $key,
                'value' => $value
            ]
        ];

        $data->options = new stdClass();
        $data->options->overwrite = $overwrite;

        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Creates a global value
     * @param stdClass $globalValue An object containing the following properties:
     *  - string $name The descriptive name of the global value
     *  - string $typeId The associated type identifier (refer to the Global Values class for valid type IDs)
     *  - string $key The internal unique identifier for this field
     *  - string $value The assigned value of the field
     * @return FourLeadsResponse
     */
    public function create(\stdClass $globalValue): FourLeadsResponse
    {
        $path = '/storage';
        $url = $this->buildUrl($path);
        return $this->makeRequest('POST', $url, $globalValue);
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
     * @return FourLeadsResponse
     */
    public function update(string $key, \stdClass $globalValue): FourLeadsResponse
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->buildUrl($path);
        return $this->makeRequest('PUT', $url, $globalValue);
    }

    /**
     * Deletes a global value for a specific key
     * @param string $key
     * @return FourLeadsResponse
     */
    public function delete(string $key): FourLeadsResponse
    {
        $path = '/storage/' . urlencode($key);
        $url = $this->buildUrl($path);
        return $this->makeRequest('DELETE', $url);
    }
}