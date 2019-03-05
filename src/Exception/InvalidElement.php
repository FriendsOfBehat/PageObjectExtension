<?php declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Exception;

use FriendsOfBehat\PageObjectExtension\Element\Element;

final class InvalidElement extends \Exception
{
    public static function forName(string $className): self
    {
        return new self("Invalid Element class name given: '$className'.");
    }

    public static function forObject($element): self
    {
        return new self("Invalid Element object '" . get_class($element) . "'. Must implement " . Element::class);
    }
}
