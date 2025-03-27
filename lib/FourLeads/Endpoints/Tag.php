<?php

namespace FourLeads\Endpoints;

use FourLeads\FourLeadsAPI;
use stdClass;

class Tag
{
    //modes to structure the tag list
    const TAG_LIST_MODE_DEFAULT = 0;
    const TAG_LIST_MODE_IDS = 1;
    const TAG_LIST_MODE_SIMPLE = 2;

    private FourLeadsAPI $client;

    public function __construct(FourLeadsAPI $client)
    {
        $this->client = $client;
    }

    /**
     * Get a List of Tags.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @param int $mode Defines how the list should be structured
     * @return stdClass Response Object
     */
    public function getTagList(int $pageNum = 0, int $pageSize = 50, string $searchString = "", int $mode = self::TAG_LIST_MODE_DEFAULT): stdClass
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
        $url = $this->client->buildUrl($path, $queryParams);
        return $this->client->makeRequest('GET', $url);
    }

    /**
     * Get a tag by id.
     * @param int $id 4leads internal id of tag
     * @return stdClass Response Object
     */
    public function getTag(int $id): stdClass
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('GET', $url);
    }

    /**
     * Create a new Tag.
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function createTag(string $name): stdClass
    {
        $path = '/tags';
        $body = new stdClass();
        $body->name = $name;
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('POST', $url, $body);
    }

    /**
     * Update a Tag.
     * @param int $id the id of the tag
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function updateTag(int $id, string $name): stdClass
    {
        $path = '/tags/' . urlencode($id);
        $body = new stdClass();
        $body->name = $name;
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('PUT', $url, $body);
    }

    /**
     * Delete a Tag.
     * @param int $id the id of the tag
     * @return stdClass Response Object
     */
    public function deleteTag(int $id): stdClass
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->client->buildUrl($path);
        return $this->client->makeRequest('DELETE', $url);
    }
}