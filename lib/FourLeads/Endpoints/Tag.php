<?php

namespace FourLeads\Endpoints;


use FourLeads\FourLeadsResponse;
use stdClass;

class Tag extends Endpoint
{
    //modes to structure the tag list
    const TAG_LIST_MODE_DEFAULT = 0;
    const TAG_LIST_MODE_IDS = 1;
    const TAG_LIST_MODE_SIMPLE = 2;


    /**
     * Get a List of Tags.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @param int $mode Defines how the list should be structured
     * @return stdClass Response Object
     */
    public function list(int $pageNum = 0, int $pageSize = 50, string $searchString = "", int $mode = self::TAG_LIST_MODE_DEFAULT): FourLeadsResponse
    {
        $path = '/tags';

        $queryParams = [];
        if ($mode != self::TAG_LIST_MODE_DEFAULT) {
            $queryParams['mode'] = $mode;
        }
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        if (strlen($searchString)) {
            $queryParams['searchString'] = $searchString;
        }
        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a tag by id.
     * @param int $id 4leads internal id of tag
     * @return stdClass Response Object
     */
    public function get(int $id): FourLeadsResponse
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Create a new Tag.
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function create(string $name): FourLeadsResponse
    {
        $path = '/tags';
        $body = new stdClass();
        $body->name = $name;
        $url = $this->buildUrl($path);
        return $this->makeRequest('POST', $url, $body);
    }

    /**
     * Update a Tag.
     * @param int $id the id of the tag
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function update(int $id, string $name): FourLeadsResponse
    {
        $path = '/tags/' . urlencode($id);
        $body = new stdClass();
        $body->name = $name;
        $url = $this->buildUrl($path);
        return $this->makeRequest('PUT', $url, $body);
    }

    /**
     * Delete a Tag.
     * @param int $id the id of the tag
     * @return stdClass Response Object
     */
    public function delete(int $id): FourLeadsResponse
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->buildUrl($path);
        return $this->makeRequest('DELETE', $url);
    }
}