# PageObjectExtension

Provides default classes for Page object pattern in Behat.

### Page

`Page` represents specific page on web, API etc.
This concept is extracted from [Sylius Behat system](https://github.com/Sylius/Sylius/tree/master/src/Sylius/Behat/Page) and
inspired by [sensiolabs/BehatPageObjectExtension](https://github.com/sensiolabs/BehatPageObjectExtension/tree/master/src/PageObject)

### Element

`Element` represents part of the page. This concept is extracted from [SyliusAdminOrderCreation](https://github.com/Sylius/AdminOrderCreationPlugin/blob/master/tests/Behat/Element/Element.php).

### SymfonyPage

`SymfonyPage` is an extension of `Page` class for better and more straightforward Symfony application support.
This concept is also extracted from [Sylius Behat system](https://github.com/Sylius/Sylius/tree/master/src/Sylius/Behat/Page)

## Installation

```bash
composer require friends-of-behat/page-object-extension --dev
```
