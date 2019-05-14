<?php

namespace FourLeads;

/**
 * Helper class with constants.
 * Class Contact
 * @package FourLeads
 */
class GlobalField
{
    //different type constants
    const TYPE_TEXT = 'text';
    const TYPE_DATETIME = 'datetime';
    //accepts doubles and integers, saved as (20,6) decimal value
    const TYPE_NUMERIC = 'numeric';
    //provided values will be summed up and not replaced, negative values possible
    const TYPE_NUMERIC_SUM = 'numeric-sum';
    //complex field types (config object), only read for now
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_RADIO = 'radio';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
}