<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Page;

interface PageInterface
{
    /**
     * @throws UnexpectedPageException If page is not opened successfully
     */
    public function open(array $urlParameters = []): void;

    public function tryToOpen(array $urlParameters = []):void;

    /**
     * @throws UnexpectedPageException
     */
    public function verify(array $urlParameters = []): void;

    public function isOpen(array $urlParameters = []): bool;
}
