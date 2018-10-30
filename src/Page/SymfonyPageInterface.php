<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Page;

interface SymfonyPageInterface extends PageInterface
{
    /**
     * @return string
     */
    public function getRouteName(): string;

    /**
     * @throws UnexpectedPageException
     */
    public function verifyRoute(array $requiredUrlParameters = []): void;
}
