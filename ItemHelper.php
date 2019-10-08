<?php

defined('_JEXEC') or die;

/**
 * Class ItemHelper
 *
 * A helper class designed to perform some common functions to make life easier when working within template overrides
 * for Joomla 3.7 and above.
 *
 * This is designed to work with Joomla 3. We cannot guarantee that this code will run with Joomla 4, currently in
 * alpha, as it will be introducing new functionality (and deprecating others) that may require this helper be tweaked.
 * Stay tuned for updates regarding Joomla 4 support.
 *
 * This includes specifically helping to streamline access to custom fields, available from Joomla 3.7.
 *
 * To use, first, include the class in your code, such as:
 *  $app  = JFactory::getApplication();
 *  $path = JPATH_THEMES . DIRECTORY_SEPARATOR . $app->getTemplate() . DIRECTORY_SEPARATOR . 'helpers' .
 *  DIRECTORY_SEPARATOR . 'ItemHelper.php'; JLoader::register('ItemHelper', $path);
 *
 * Then, pass an item to the process function:
 *  ItemHelper::process($this->item)
 *
 * Depending on where your override is and how you access the item, it may be $this->item or $item - check your
 * override usage for more details.
 *
 * @author  Marty Friedel
 * @version 1.1
 * @see     https://github.com/martyf/joomla-item-helper
 */
class ItemHelper
{
    /**
     * Truncate a string to a given number of characters, keeping whole words.
     *
     * The final string may be longer than the defined $numberOfCharacters because we don't want to break words.
     * Behaviour could be tweaked to make the string be NO LONGER THAN, however this behaviour has worked better for us.
     *
     * @param string $string             The string to process
     * @param int    $numberOfCharacters Optional. The number of characters for our string.
     *                                   Default 160 if not supplied.
     *
     * @return string
     * @since 1.1
     */
    public static function truncate($string, $numberOfCharacters = 160)
    {
        // strip the tags
        $string = strip_tags($string);

        // if the length of the string is less than the number of characters, simply return
        if (strlen($string) < $numberOfCharacters)
        {
            return $string;
        }

        // the individual words of our input string
        $words = explode(' ', $string);

        // the current number of characters in our new string
        $counter = 0;

        // an array for storing our words
        $use = array();

        // loop through all of the words
        foreach ($words as $word)
        {
            // if the tally of characters is greater than or equal to the total number needed, leave the loop
            if ($counter >= $numberOfCharacters)
            {
                // exit the loop
                break;
            }

            // use the word
            $use[] = $word;

            // update the counter
            $counter += strlen($word) + 1; // add 1 to add a space after the word
        }

        // join the array, add the ellipsis, and return
        return implode(' ', $use) . '...';
    }

    /**
     * Perform processing on a given Item.
     *
     * Processing currently involves translating the jcfields array to a jcfieldsnames array for name-based access.
     *
     * Additional helper functions have been defined in this class to assist with accessing fields by name (and
     * returning their group, label, options or value).
     *
     * @param stdClass $item The item to process (i.e. an Article)
     *
     * @return stdClass The processed input item
     * @since 1.0
     */
    public static function process(&$item)
    {
        // define a default type for the Item - an article
        $type = 'com_content.article';

        // if a type alias has been defined, let's update our local type
        if (isset($item->type_alias))
        {
            $type = $item->type_alias;
        }

        // convert keys to array for easier access
        $item->jcfieldsnames = [];
        if (!isset($item->jcfields))
        {
            // get the fields if they're not loaded
            $item->jcfields = FieldsHelper::getFields($type, $item, true);
        }

        if (is_array($item->jcfields) && count($item->jcfields) > 0)
        {
            // loop through the jcfields array
            foreach ($item->jcfields as $field)
            {
                // add a new
                $item->jcfieldsnames[ $field->name ] = [
                    // save the name of the field
                    'name'    => $field->name,

                    // determine the value
                    // if its an array, and only one, just return the plain value - this is so we don't need to dig
                    // inside arrays at the template level for single-option values.
                    'value'   => is_array($field->rawvalue) && count($field->rawvalue) == 1 ? $field->rawvalue[0] : $field->rawvalue,

                    // the display label of the Field
                    'label'   => $field->label,

                    // The Field Group ID
                    // This is really useful if you want to filter on iteration
                    'group'   => $field->group_id,

                    // Field options
                    // For types like Checkbox or List, this will be all available (possible) options
                    'options' => $field->fieldparams->get('options')
                ];

                if ($field->type == 'checkboxes')
                {
                    // create an array for our selected items
                    $selected = [];

                    // loop through all of the options
                    foreach ($field->fieldparams->get('options') as $option)
                    {
                        // check if the selected value(s) has the current option
                        // if rawvalue is an array, there is 2 or more options - look inside the rawvalue as an array
                        // if rawvalue is a string, there's only one option - comparitor is as a string
                        if ((is_array($field->rawvalue) && in_array($option->value, $field->rawvalue)) || $field->rawvalue == $option->value)
                        {
                            $selected[] = array(
                                'name'  => $option->name,
                                'value' => $option->value
                            );
                        }
                    }

                    // update the value with the selected items
                    $item->jcfieldsnames[ $field->name ]['value'] = $selected;
                }
                else if (ItemHelper::isJSON($item->jcfieldsnames[ $field->name ]['value']))
                {
                    // the value is a JSON string - so decode it to be an associative array
                    $item->jcfieldsnames[ $field->name ]['value'] = json_decode($item->jcfieldsnames[ $field->name ]['value'], true);
                    //
                    // Side note:
                    //
                    // Repeatable fields that use "editor" will have their HTML stripped out. Best to avoid as the
                    // Joomla dev team have advised they won't make other changes to repeatable "editor" bugs given
                    // the Joomla 4 changes to this entire component.
                    // See https://issues.joomla.org/tracker/joomla-cms/24343
                    //
                }

            }
        }

        // finally, set a flag on the item saying that the process function has been called
        $item->isItemHelperProcessed = true;

        // return the item, in case you do set it back to the source
        return $item;
    }


    /**
     * Get a specific property for a Custom Field within an Item (i.e. Article).
     *
     * The Field is the name of the Custom Field, and Property is one one:
     *  - group
     *  - label
     *  - options
     *  - value
     *
     * @param stdClass $item     The item containing the field
     * @param string   $field    The field name
     * @param string   $property The property of the field
     *
     * @return bool|mixed   Return the property, or false if not found
     * @since 1.0
     */
    protected static function getFieldProperty($item, $field, $property)
    {
        // check if we have loaded the item already
        if (!isset($item->isItemHelperProcessed) || $item->isItemHelperProcessed !== true)
        {
            ItemHelper::process($item);
        }

        // check we have jcfieldsnames
        if (!isset($item->jcfieldsnames))
        {
            return false;
        }

        // look in the jcfieldsnames array for the given field
        if (array_key_exists($field, $item->jcfieldsnames))
        {
            // we found the field, let's return the property
            return $item->jcfieldsnames[ $field ][ $property ];
        }

        // make it this far, return false
        return false;
    }

    /**
     * Get a Custom Field's Group ID
     *
     * @param stdClass $item  The item with the Custom Field
     * @param string   $field The field name to find
     *
     * @return bool|string   Return the Group, or false if not found
     * @since 1.0
     */
    public static function getFieldGroupId($item, $field)
    {
        return ItemHelper::getFieldProperty($item, $field, 'group');
    }

    /**
     * Get a Custom Field's Label
     *
     * @param stdClass $item  The item with the Custom Field
     * @param string   $field The field name to find
     *
     * @return bool|string   Return the Label, or false if not found
     * @since 1.0
     */
    public static function getFieldLabel($item, $field)
    {
        return ItemHelper::getFieldProperty($item, $field, 'label');
    }

    /**
     * Get a Custom Field's Options
     *
     * @param stdClass $item  The item with the Custom Field
     * @param string   $field The field name to find
     *
     * @return bool|array   Return the Options, or false if not found
     * @since 1.0
     */
    public static function getFieldOptions($item, $field)
    {
        return ItemHelper::getFieldProperty($item, $field, 'options');
    }

    /**
     * Get a Custom Field's Value
     *
     * @param stdClass $item  The item with the Custom Field
     * @param string   $field The field name to find
     *
     * @return bool|string|array   Return the Value, or false if not found
     *                             Value may be a string or an array
     * @since 1.0
     */
    public static function getFieldValue($item, $field)
    {
        return ItemHelper::getFieldProperty($item, $field, 'value');
    }

    /**
     * Simple helper function to determine if the string is JSON or not
     *
     * Defined as public in case we need to use it at the template override level
     *
     * @param string $string A string to test
     *
     * @return bool True if string is JSON, false otherwise
     * @since 1.0
     */
    public static function isJSON($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}