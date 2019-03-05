# PageObjectExtension

Provides default classes for Page object pattern in Behat.

### Page

`Page` represents specific page on web, API etc.
This concept is extracted from [Sylius Behat system](https://github.com/Sylius/Sylius/tree/master/src/Sylius/Behat/Page) and
inspired by [sensiolabs/BehatPageObjectExtension](https://github.com/sensiolabs/BehatPageObjectExtension/tree/master/src/PageObject)

### SymfonyPage

`SymfonyPage` is an extension of `Page` class for better and more straightforward Symfony application support.
This concept is also extracted from [Sylius Behat system](https://github.com/Sylius/Sylius/tree/master/src/Sylius/Behat/Page)

### Element

`Element` represents part of the page. This concept is extracted from [SyliusAdminOrderCreation](https://github.com/Sylius/AdminOrderCreationPlugin/blob/master/tests/Behat/Element/Element.php).

When extending Element, specify the `$locator` property to specify the element on the page. 

In the optional `$innerElements` array you can specify a named list of css locators of elements inside your element.

```php
class CategoryBar extends Element
{
    protected $locator = '#categoryBar';
    
    protected $innerElements = [
        'items' => 'li:not(.more)',
    ];

    public function hasNumberOfCategories(int $numberOfCategories): bool
    {
        return $numberOfCategories === count($this->getElements('items'));
    }

    public function hasCategory(string $category): bool
    {
        return strpos($this->getText(), $category) !== false;
    }
}
```

## Installation

```bash
composer require friends-of-behat/page-object-extension --dev
```
