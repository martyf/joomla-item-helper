# Joomla ItemHelper

This Joomla ItemHelper is used in template overrides to make some common actions much easier (and help centralise 
some repetitive logic so that we don't have to copy and paste certain code over and over again).

This is aimed at developers who create their own templates for Joomla, and are comfortable working with PHP.

## How to use ItemHelper

Within the override PHP file, you need to include the ItemHelper. I put it in my template's folder, under a folder called
`helpers`. You can place it where you like, but just remember to update the path below.

```PHP
$app  = JFactory::getApplication();
$path = JPATH_THEMES . DIRECTORY_SEPARATOR . $app->getTemplate() . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'ItemHelper.php';
JLoader::register('ItemHelper', $path);
```

To run the magic, then simply call ItemHelper's `process` method, passing in your item - such as `$this->item` or `$item`, 
depending on the override type you're working with.
```PHP
ItemHelper::process($this->item)
```

This will update your item, and not override any

## Custom Fields

Custom Fields have been in Joomla 3 since 3.7, and are incredibly powerful and flexible. 

But accessing the Custom Fields at the override level is a little repetitive - and if we need it in multiple overrides,
that can be a lot of code replication.

Centralising this processing of Custom Fields makes it easier to include in multiple overrides, but keep the core
processing logic in one place.

### Processing Custom Fields

The ItemHelper's `process` method will look at your item's Custom Fields, and create a new array called `jcfieldsnames`.

### Accessing a Custom Field

After the processing, you can iterate over `$this->item->jcfieldsnames` like any other array.

Alternatively, some helper functions have been created that make it easy for you to get a Custom Field's:
- label
- group ID
- options (for Field Types that have options)
- value

These helpers will return the property, or false if not found.

To get a specific field's value, use `getFieldValue`. This can return a `string` for single selections, or `array` for 
multiple (such as checkboxes).

```PHP
ItemHelper::getFieldValue($this->item, 'my-field');
```

To get a specific field's label, use `getFieldLabel`.

```PHP
ItemHelper::getFieldLabel($this->item, 'my-field');
```

To get a specific field's Field Group ID, use `getFieldGroupId`.

```PHP
ItemHelper::getFieldGroupId($this->item, 'my-field');
```

To get a specific field's options, use `getFieldOptions`. This only applies to Field Types that use options, such as List
or Checkboxes. Other Field Types will return an empty `array`.

```PHP
ItemHelper::getFieldOptions($this->item, 'my-field');
```

## Requests or issues

If you're having any issues working with the ItemHelper, 
[start a new issue](https://github.com/martyf/joomla-item-helper/issues) in this repository.

Any requests for specific field type handling can be added there too.

Need a Joomla expert? I work for [Mity Digital](https://www.mity.com.au), a Melbourne-based web design and development 
agencey. We love working with Joomla - come and say hello!