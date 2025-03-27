<?php
/**
 * This libary allows you to quickly and easily perform REST actions on the 4leads backend using PHP.
 *
 * @author    Bertram Buchardt <support@4leads.de>
 * @copyright 2018 4leads GmbH
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace FourLeads;

use stdClass;

/**
 * Interface to the 4leads Web API
 */
class FourLeadsAPI
{
    const VERSION = '1.0.6';
    const TOO_MANY_REQUESTS_HTTP_CODE = 429;
    //modes to structure the tag list
    const TAG_LIST_MODE_DEFAULT = 0;
    const TAG_LIST_MODE_IDS = 1;
    const TAG_LIST_MODE_SIMPLE = 2;


    //Client properties
    /**
     * Endpoint host
     * @var string
     */
    protected $host;

    /**
     * CURL-Request headers
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $version;

    /**
     * Query Path part
     * @var array
     */
    protected $path;

    /**
     * curl options array
     * @var array
     */
    protected $curlOptions;
    //END Client properties

    /**
     * Setup the HTTP Client
     *
     * @param string $apiKey your 4leads API Key.
     * @param array $options an array of options, currently only "host" and "curl" are implemented.
     */
    public function __construct($apiKey, array $options = [])
    {
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'User-Agent: four-leads-api/' . self::VERSION . ';php',
            'Accept: application/json',
        ];

        $host = isset($options['host']) ? $options['host'] : 'https://api.4leads.net';

        $curlOptions = isset($options['curl']) ? $options['curl'] : null;
        $this->setupClient($host, $headers, '/v1', null, $curlOptions);
    }

    /**
     * Initialize the client
     *
     * @param string $host the base url (e.g. https://api.4leads.net)
     * @param array|null $headers global request headers
     * @param string|null $version api version (configurable) - this is specific to the 4leads API
     * @param array|null $path holds the segments of the url path
     * @param array|null $curlOptions extra options to set during curl initialization
     */
    protected function setupClient(
        string  $host,
        ?array  $headers = null,
        ?string $version = null,
        ?array  $path = null,
        ?array  $curlOptions = null
    ): void
    {
        $this->host = $host;
        $this->headers = $headers ?: [];
        $this->version = $version;
        $this->path = $path ?: [];
        $this->curlOptions = $curlOptions ?: [];
    }

    /**
     * Get a List of Contacts
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString A basic searchstring matching firstname, lastname and email
     * @param int $mode Mode of the results. Possible values are 1,2 or null for default
     * @param int $status Filter the status of the contacts (1-6) (deprecated)
     * @return stdClass Response Object
     */
    public function getContactList(int $pageNum = 0, int $pageSize = 50, string $searchString = "", int $mode = null, int $status = null)
    {
        $path = '/contacts';

        $queryParams = [];
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        $queryParams['searchString'] = $searchString;

        if (is_numeric($mode)) {
            $queryParams['mode'] = $mode;
        }
        if (isset($status)) {
            $queryParams['status'] = $status;
        }
        $url = $this->buildUrl($path, $queryParams);

        $response = $this->makeRequest('GET', $url);

        return $response;
    }

    /**
     * Build the final URL to be passed
     * @param string $path $the relative Path inside the api
     * @param array $queryParams an array of all the query parameters
     * @return string
     */
    public function buildUrl($path, $queryParams = null)
    {
        if (isset($queryParams) && is_array($queryParams) && count($queryParams)) {
            $path .= '?' . http_build_query($queryParams);
        }
        return sprintf('%s%s%s', $this->host, $this->version ?: '', $path);
    }

    /**
     * Make the API call and return the response.
     * This is separated into it's own function, so we can mock it easily for testing.
     *
     * @param string $method the HTTP verb
     * @param string $url the final url to call
     * @param stdClass $body request body
     * @param array $headers any additional request headers
     *
     * @return stdClass object
     */
    public function makeRequest($method, $url, $body = null, $headers = null)
    {
        $channel = curl_init($url);

        $options = $this->createCurlOptions($method, $body, $headers);

        curl_setopt_array($channel, $options);
        $content = curl_exec($channel);

        $response = $this->parseResponse($channel, $content);

        curl_close($channel);

        if (strlen($response->responseBody)) {
            $response->responseBody = json_decode($response->responseBody);
        }

        return $response;
    }

    /**
     * Creates curl options for a request
     * this function does not mutate any private variables
     *
     * @param string $method
     * @param stdClass $body
     * @param array $headers
     *
     * @return array
     */
    private function createCurlOptions($method, $body = null, $headers = null)
    {
        $options = [
                CURLOPT_HEADER => true,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_FAILONERROR => false,
                CURLOPT_USERAGENT => '4leads php-cli-client,v' . self::VERSION,
            ] + $this->curlOptions
            + [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_RETURNTRANSFER => true,
            ];

        if (isset($headers)) {
            $headers = array_merge($this->headers, $headers);
        } else {
            $headers = $this->headers;
        }

        if (isset($body)) {
            $encodedBody = json_encode($body);
            $options[CURLOPT_POSTFIELDS] = $encodedBody;
            $headers = array_merge($headers, ['Content-Type: application/json']);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        return $options;
    }

    /**
     * Prepare response object.
     *
     * @param \CurlHandle $channel the curl resource
     * @param string $content
     *
     * @return FourLeadsResponse|stdClass  response object
     */
    private function parseResponse(\CurlHandle $channel, $content)
    {
        $response = new FourLeadsResponse();
        $response->headerSize = curl_getinfo($channel, CURLINFO_HEADER_SIZE);
        $response->statusCode = curl_getinfo($channel, CURLINFO_HTTP_CODE);

        $response->responseBody = substr($content, $response->headerSize);

        $headString = substr($content, 0, $response->headerSize);
        $response->responseHeaders = explode("\n", $headString);
        $response->responseHeaders = array_map('trim', $response->responseHeaders);

        return $response;
    }

    /**
     * Trigger used by Wordpress Plugin (bulk of events)
     * @param stdClass $body
     * @param $integrationId
     * @param string $token
     * @return stdClass
     */
    public function wpTriggerEvents(stdClass $body, $integrationId, string $token)
    {
        $path = '/integrations/fl-plugin/' . urlencode($integrationId) . '/trigger-events';
        $body->token = $token;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Used by Wordpress Plugin to stop Wordpress-Automation if delete inside wp
     * @param int $automationId
     * @param $integrationId
     * @param string $token
     * @return stdClass
     */
    public function wpStopAutomation(int $automationId, $integrationId, string $token)
    {
        $path = '/integrations/fl-plugin/' . urlencode($integrationId) . '/stop-automation';
        $body = new stdClass();
        $body->token = $token;
        $body->automationId = $automationId;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Check which of the given tags are set for the contact
     * @param int|string $idOrEmail 4leads internal id of contact (recommended) or email (fallback)
     * @param array $tagIds array of numeric tagIds
     * @return stdClass Response Object
     */
    public function compareContactTags($idOrEmail, array $tagIds)
    {
        $path = '/contacts/' . urlencode($idOrEmail) . '/compareTagList';
        $queryParams = [];
        foreach ($tagIds as $index => $tagId) {
            $queryParams['tagIds[' . $index . ']'] = $tagId;
        }
        $url = $this->buildUrl($path, $queryParams);
        $response = $this->makeRequest('GET', $url, $queryParams);
        return $response;
    }



    /**
     * Test the API-KEY
     * @return bool
     */
    public function validateKey()
    {
        $path = '/ping';
        $url = $this->buildUrl($path, []);
        $response = $this->makeRequest('GET', $url);
        return $response->statusCode == 200;
    }

    /**
     * Get a List of Tags.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @param int $mode Defines how the list should be structured
     * @return stdClass Response Object
     */
    public function getTagList(int $pageNum = 0, int $pageSize = 50, string $searchString = "", $mode = self::TAG_LIST_MODE_DEFAULT)
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
        $response = $this->makeRequest('GET', $url);

        return $response;
    }

    /**
     *
     * @param int $pageNum
     * @param int $pageSize
     * @param string $searchString
     * @param array $tagIds max 20 ids in one request
     * @return stdClass Response Object
     */
    public function getFormSnippets(int $pageNum = 0, int $pageSize = 100, string $searchString = '')
    {
        $path = '/campaigns/snippets';
        $queryParams = [];

        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        if (strlen($searchString)) {
            $queryParams['searchString'] = $searchString;
        }
        $url = $this->buildUrl($path, $queryParams);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     *
     * @param int|string $integrationId
     * @param string $token
     * @param array $tagIds max 20 ids in one request
     * @return stdClass
     */
    public function addSyncTags($integrationId, string $token, array $tagIds)
    {
        $path = '/integrations/fl-plugin/' . urlencode($integrationId) . '/tags';
        $body = new stdClass();
        $body->token = $token;
        $body->tagIds = $tagIds;
        $url = $this->buildUrl($path);
        return $this->makeRequest('POST', $url, $body);
    }

    /**
     * @param $integrationId
     * @param string $token
     * @return stdClass
     */
    public function getFunctionList($integrationId, string $token)
    {
        $path = '/integrations/fl-plugin/' . urlencode($integrationId) . '/function-list';
        $body = new stdClass();
        $body->token = $token;
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url, $body);
    }

    /**
     *
     * @param int|string $integrationId
     * @param string $token
     * @param array $tagIds max 20 ids in one request
     * @return stdClass
     */
    public function removeSyncTags($integrationId, string $token, array $tagIds)
    {
        $path = '/integrations/fl-plugin/' . urlencode($integrationId) . '/tags';
        $body = new stdClass();
        $body->token = $token;
        $body->tagIds = $tagIds;
        $url = $this->buildUrl($path);
        return $this->makeRequest('DELETE', $url, $body);
    }

    /**
     * Get a List of Opt-ins.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @return stdClass Response Object
     */
    public function getOptInList(int $pageNum = 0, int $pageSize = 50, string $searchString = "")
    {
        $path = '/opt-ins';

        $queryParams = [];
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        $queryParams['searchString'] = $searchString;

        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a List of campaigns.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @return stdClass Response Object
     */
    public function getCampaignList(int $pageNum = 0, int $pageSize = 50, string $searchString = "")
    {
        $path = '/campaigns';

        $queryParams = [];
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        $queryParams['searchString'] = $searchString;

        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a List of global fields.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @return stdClass Response Object
     */
    public function getGlobalFieldList(int $pageNum = 0, int $pageSize = 50, string $searchString = "")
    {
        $path = '/globalFields';

        $queryParams = [];
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        $queryParams['searchString'] = $searchString;

        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a List of opt-in-cases.
     * @param int $pageNum Which page of the results schould be retrieved (starting with 0)
     * @param int $pageSize Number of results per page (max 200)
     * @param string $searchString a basic searchstring matching name
     * @return FourLeadsResponse Response Object
     */
    public function getOptInCaseList(int $pageNum = 0, int $pageSize = 50, string $searchString = ""): FourLeadsResponse
    {
        $path = '/opt-in-cases';

        $queryParams = [];
        $queryParams['pageNum'] = $pageNum;
        $queryParams['pageSize'] = $pageSize;
        $queryParams['searchString'] = $searchString;

        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a contact by id.
     * @param int|string $id 4leads internal id of contact
     * @param array $embed array for some Options to embed additional data into the contact-object.
     * @return stdClass Response Object
     */
    public function getContact($id, array $embed = [])
    {
        $path = '/contacts/' . urlencode($id);
        $queryParams = [];
        if (count($embed)) {
            $queryParams['embed'] = $embed;
        }
        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a List of GlobalFields which have a value for this contact. The value will be in the "_value" field of each
     * field-object
     * @param int|string $idOrEmail 4leads internal id of contact (recommended) or email (fallback)
     * @param array $filterIds array of field ids. if set only those fields are retrieved
     * @return stdClass Response Object
     */
    public function getContactFields($idOrEmail, array $filterIds = [])
    {
        $path = '/contacts/' . urlencode($idOrEmail) . '/getFieldList';
        $queryParams = [];
        if (count($filterIds)) {
            foreach ($filterIds as $index => $id) {
                $queryParams["fieldIds[$index]"] = $id;
            }
        }
        $url = $this->buildUrl($path, $queryParams);
        return $this->makeRequest('GET', $url);
    }


    /**
     * Get a List of Tags which ar active for this contact
     * @param int|string $idOrEmail 4leads internal id of contact (recommended) or email (fallback)
     * @return stdClass Response Object
     */
    public function getContactTags($idOrEmail)
    {
        $path = '/contacts/' . urlencode($idOrEmail) . '/getTagList';
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a tag by id.
     * @param int $id 4leads internal id of tag
     * @return stdClass Response Object
     */
    public function getTag(int $id)
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a optin by id.
     * @param int $id 4leads internal id of optin
     * @return stdClass Response Object
     */
    public function getOptIn(int $id)
    {
        $path = '/opt-ins/' . urlencode($id);
        $url = $this->buildUrl($path);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get a campaign by id.
     * @param int $id 4leads internal id of campaign
     * @return stdClass Response Object
     */
    public function getCampaign(int $id)
    {
        $path = '/campaigns/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     * Get a global field by id.
     * @param int $id 4leads internal id of the global field
     * @return stdClass Response Object
     */
    public function getGlobalField(int $id)
    {
        $path = '/globalFields/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     * Creates a new global field
     * @param stdClass $globalField see the properties on $this->getGlobalFieldList()
     * @return stdClass Response Object
     */
    public function createGlobalField(stdClass $globalField)
    {
        $path = '/globalFields';

        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $globalField);
        return $response;
    }

    /**
     * Update global field
     * @param int $globalFieldId the id of the field to update
     * @param stdClass $globalField see the properties on $this->getGlobalFieldList()
     * @return stdClass Response Object
     */
    public function updateGlobalField(int $globalFieldId, stdClass $globalField)
    {
        $path = '/globalFields/' . urlencode($globalFieldId);

        $url = $this->buildUrl($path);
        $response = $this->makeRequest('PUT', $url, $globalField);
        return $response;
    }

    /**
     * Delete global field. All saved connected values will be lost.
     * @param int $globalFieldId the id of the field to update
     * @return stdClass Response Object
     */
    public function deleteGlobalField(int $globalFieldId)
    {
        $path = '/globalFields/' . urlencode($globalFieldId);

        $url = $this->buildUrl($path);
        $response = $this->makeRequest('DELETE', $url);
        return $response;
    }

    /**
     * Get the value of a global field which is set for the given contact.
     * @param int $globalFieldId the id of the field
     * @param int $contactId the id of the contact
     * @return stdClass Response Object
     */
    public function getGlobalFieldValue(int $globalFieldId, int $contactId)
    {
        $path = '/globalFields/' . urlencode($globalFieldId) . '/getValue';
        $queryParams = [
            'contactId' => $contactId,
        ];
        $url = $this->buildUrl($path, $queryParams);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     * Set the value of a global field which is set for the given contact.
     * @param int $globalFieldId the id of the field
     * @param int $contactId the id of the contact
     * @param mixed $value The value to set
     * @param bool $doTriggers If true alle events which listen on field value changes will be fired if value changes.
     * @param bool $overwrite if false only empty values will be overwriten. if true all values will be overwritten.
     * @return stdClass Response Object
     */
    public function setGlobalFieldValue(int $globalFieldId, int $contactId, $value, bool $doTriggers = true, bool $overwrite = true)
    {
        $path = '/globalFields/' . urlencode($globalFieldId) . '/setValue';
        $body = new stdClass();
        $body->contactId = $contactId;
        $body->value = $value;
        $body->doTriggers = $doTriggers;
        $body->overwrite = $overwrite;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Set multiple values for several global fields on this contact.
     * Array of fields should be created by GlobalField::addToFieldList(...)
     * @param int $contactId the id of the contact
     * @param array $fieldList array of FieldValueSet Objects !!not more than 20 Objects allowed!!
     * @return stdClass
     */
    public function setGlobalFieldList(int $contactId, array $fieldList)
    {
        $path = '/globalFields/setFieldList';
        $body = new stdClass();
        $body->contactId = $contactId;
        $body->fields = $fieldList;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Get a optin case by id.
     * @param int $id 4leads internal id of optin case
     * @return stdClass Response Object
     */
    public function getOptInCase(int $id)
    {
        $path = '/opt-in-cases/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     * Create a new Tag.
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function createTag(string $name)
    {
        $path = '/tags';
        $body = new stdClass();
        $body->name = $name;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Update a Tag.
     * @param int $id the id of the tag
     * @param string $name The name of the Tag
     * @return stdClass Response Object
     */
    public function updateTag(int $id, string $name)
    {
        $path = '/tags/' . urlencode($id);
        $body = new stdClass();
        $body->name = $name;
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('PUT', $url, $body);
        return $response;
    }

    /**
     * Delete a Tag.
     * @param int $id the id of the tag
     * @return stdClass Response Object
     */
    public function deleteTag(int $id)
    {
        $path = '/tags/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('DELETE', $url);
        return $response;
    }

    /**
     * Grants an opt-in-case for a given contact
     * @param int $contactId the id of the contact
     * @param int $optinCaseId the id of the
     * @param string $ip The IP-Adresse to log who granted the opt-in-case, leave null if granted by system
     * @return stdClass Response Object
     */
    public function grantOptInCase(int $contactId, int $optinCaseId, string $ip = null)
    {
        $path = '/opt-in-cases/' . urlencode($optinCaseId) . '/grant';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->contactId = $contactId;
        if (isset($ip)) {
            $body->ip = $ip;
        }
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Sends an opt-in email to confirm the email address.
     * @param int $contactId the id of the contact
     * @param int $optinId the id of the opt-in-process
     * @return stdClass Response Object
     */
    public function sendOptIn(int $contactId, int $optinId)
    {
        $path = '/opt-ins/' . urlencode($optinId) . '/send';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->contactId = $contactId;
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Starts a campaign for the contact.
     * @param int $contactId the id of the contact
     * @param int $campaignId the id of the campaign
     * @return stdClass Response Object
     */
    public function startCampaign(int $contactId, int $campaignId)
    {
        $path = '/campaigns/' . urlencode($campaignId) . '/start';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->contactId = $contactId;
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Stops a campaign for the contact.
     * @param int $contactId the id of the contact
     * @param int $campaignId the id of the campaign
     * @return stdClass Response Object
     */
    public function stopCampaign(int $contactId, int $campaignId)
    {
        $path = '/campaigns/' . urlencode($campaignId) . '/stop';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->contactId = $contactId;
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * revoke an opt-in-case for a given contact
     * @param int $contactId the id of the contact
     * @param int $optinCaseId the id of the
     * @param string $ip The IP-Adresse to log who revoked the opt-in-case, leave null if revoked by system
     * @return stdClass Response Object
     */
    public function revokeOptInCase(int $contactId, int $optinCaseId, string $ip = null)
    {
        $path = '/opt-in-cases/' . urlencode($optinCaseId) . '/revoke';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->contactId = $contactId;
        if (isset($ip)) {
            $body->ip = $ip;
        }
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Add a List of Tags(IDs) to a contact
     * @param int $contactId the id of the contact
     * @param array $tagIds array of tags to be set !!not more than 20 ids allowed!!
     * @return stdClass Response Object
     */
    public function addTagList(int $contactId, array $tagIds)
    {
        $path = '/contacts/' . urlencode($contactId) . '/addTagList';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->tagIds = [];
        foreach ($tagIds as $tagId) {
            $body->tagIds[] = $tagId;
        }
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Remove a List of Tags(IDs) from a contact
     * @param int $contactId the id of the contact
     * @param array $tagIds array of tag ids to be removed !!not more than 20 ids allowed!!
     * @return stdClass Response Object
     */
    public function removeTagList(int $contactId, array $tagIds)
    {
        $path = '/contacts/' . urlencode($contactId) . '/removeTagList';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->tagIds = [];
        foreach ($tagIds as $tagId) {
            $body->tagIds[] = $tagId;
        }
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Add a Tag to a contact
     * @param int $contactId the id of the contact
     * @param int $tagId the id of the tag
     * @return stdClass Response Object
     */
    public function addTag(int $contactId, int $tagId)
    {
        $path = '/contacts/' . urlencode($contactId) . '/addTag';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->tagId = $tagId;
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Remove a Tag from a contact
     * @param int $contactId the id of the contact
     * @param int $tagId the id of the tag
     * @return stdClass Response Object
     */
    public function removeTag(int $contactId, int $tagId)
    {
        $path = '/contacts/' . urlencode($contactId) . '/removeTag';
        $url = $this->buildUrl($path);
        $body = new stdClass();
        $body->tagId = $tagId;
        $response = $this->makeRequest('POST', $url, $body);
        return $response;
    }

    /**
     * Get a contact by email. Might be deprecated in future version if contacts can hold multiple emails
     * @param string $email email of the contact
     * @return stdClass Response Object
     */
    public function getContactByEmail(string $email)
    {
        $path = '/contacts/' . urlencode($email);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    /**
     * Create a new Contact.
     * @param stdClass $contact the object holding the properties to set. See $this->getContact() for field names
     * @param bool $noUpdate If true a duplicate email with result in error otherwise the existing contact will be
     *     updated
     * @return stdClass Response Object
     */
    public function createContact(stdClass $contact, bool $noUpdate = false)
    {
        $path = '/contacts';
        if ($noUpdate) {
            $contact->_noUpdate = true;
        }
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('POST', $url, $contact);
        return $response;
    }

    /**
     * Update an existing contact.
     * @param int $id 4leads internal id of contact
     * @param stdClass $contact the object holding the properties to set. See $this->getContact() for field names
     * @return stdClass Response Object
     */
    public function updateContact(int $id, stdClass $contact)
    {
        $path = '/contacts/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('PUT', $url, $contact);
        return $response;
    }

    /**
     * delete an existing contact.
     * @param int $id 4leads internal id of contact
     * @return stdClass Response Object
     */
    public function deleteContact(int $id)
    {
        $path = '/contacts/' . urlencode($id);
        $url = $this->buildUrl($path);
        $response = $this->makeRequest('DELETE', $url);
        return $response;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Set extra options to set during curl initialization
     *
     * @param array $options
     *
     * @return FourLeadsAPI
     */
    public function setCurlOptions(array $options): static
    {
        $this->curlOptions = $options;

        return $this;
    }
}