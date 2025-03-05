<?php

namespace FourLeads;

/**
 * Helper class with constants.
 * Class GlobalField.
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

    /**
     * Helper function to create a fieldArray
     * @param array $fieldList
     * @param int $globalFieldId field id
     * @param mixed $value The value to set
     * @param bool $doTriggers If true alle events which listen on field value changes will be fired if value changes.
     * @param bool $overwrite if false only empty values will be overwriten. if true all values will be overwritten.
     * @return bool true if field was added
     */
    public static function addToFieldList(array &$fieldList, int $globalFieldId, $value, bool $doTriggers = true, bool $overwrite = true)
    {
        if (count($fieldList) > 19) {
            return false;
        }
        $item = new \stdClass();
        $item->globalFieldId = $globalFieldId;
        $item->value = $value;
        $item->doTriggers = $doTriggers;
        $item->overwrite = $overwrite;
        $fieldList[] = $item;
        return true;
    }
}