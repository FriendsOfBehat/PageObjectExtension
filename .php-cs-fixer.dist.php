<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
;

return (new Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => ['ignored_tags' => ['psalm-suppress', 'phpstan-ignore']],
    ])
    ->setFinder($finder)
;
