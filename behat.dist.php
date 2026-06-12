<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;

if (!defined('BEHAT_BIN_PATH')) {
    define('BEHAT_BIN_PATH', __DIR__ . '/vendor/bin/behat');
}

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('default'))
                    ->withPaths('features')
                    ->withContexts(\Tests\FriendsOfBehat\PageObjectExtension\Behat\Context\TestContext::class)
            )
    );
