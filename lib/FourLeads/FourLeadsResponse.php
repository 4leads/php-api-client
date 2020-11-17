<?php


namespace FourLeads;

/**
 * Class FourLeadsResponse
 * @package FourLeads
 * @property int headerSize
 * @property int statusCode
 * @property \stdClass|mixed|string|null responseBody
 * @property array responseHeaders
 */
class FourLeadsResponse extends \stdClass
{
    /**
     * @var int
     */
    public $headerSize;
    /**
     * @var int
     */
    public $statusCode;
    /**
     * @var mixed|\stdClass|string
     */
    public $responseBody;
    /**
     * @var array
     */
    public $responseHeaders;
}