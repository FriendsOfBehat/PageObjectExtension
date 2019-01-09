<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Page;

use Behat\Mink\Session;
use Symfony\Component\Routing\RouterInterface;

abstract class SymfonyPage extends Page implements SymfonyPageInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var array */
    protected static $additionalParameters = ['_locale' => 'en_US'];

    /**
     * @param array|\ArrayAccess $minkParameters
     */
    public function __construct(Session $session, $minkParameters, RouterInterface $router)
    {
        if (!is_array($minkParameters) && !$minkParameters instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(sprintf(
                '"$parameters" passed to "%s" has to be an array or implement "%s".',
                self::class,
                \ArrayAccess::class
            ));
        }

        parent::__construct($session, $minkParameters);

        $this->router = $router;
    }

    abstract public function getRouteName(): string;

    /**
     * @throws UnexpectedPageException
     */
    public function verifyRoute(array $requiredUrlParameters = []): void
    {
        $url = $this->getDriver()->getCurrentUrl();
        $path = parse_url($url)['path'];

        $path = preg_replace('#^/app(_dev|_test|_test_cached)?\.php/#', '/', $path);
        $matchedRoute = $this->router->match($path);

        $this->verifyRouteName($matchedRoute, $url);
        $this->verifyRouteParameters($requiredUrlParameters, $matchedRoute);
    }

    final protected function makePathAbsolute(string $path): string
    {
        $baseUrl = rtrim($this->getParameter('base_url'), '/') . '/';

        return 0 !== strpos($path, 'http') ? $baseUrl . ltrim($path, '/') : $path;
    }

    protected function getUrl(array $urlParameters = []): string
    {
        $path = $this->router->generate($this->getRouteName(), $urlParameters + static::$additionalParameters);

        $replace = [];
        foreach (static::$additionalParameters as $key => $value) {
            $replace[sprintf('&%s=%s', $key, $value)] = '';
            $replace[sprintf('?%s=%s&', $key, $value)] = '?';
            $replace[sprintf('?%s=%s', $key, $value)] = '';
        }

        $path = str_replace(array_keys($replace), array_values($replace), $path);

        return $this->makePathAbsolute($path);
    }

    protected function verifyUrl(array $urlParameters = []): void
    {
        $url = $this->getDriver()->getCurrentUrl();
        $path = parse_url($url)['path'];

        $path = preg_replace('#^/app(_dev|_test|_test_cached)?\.php/#', '/', $path);
        $matchedRoute = $this->router->match($path);

        if (isset($matchedRoute['_locale'])) {
            $urlParameters += ['_locale' => $matchedRoute['_locale']];
        }

        parent::verifyUrl($urlParameters);
    }

    /**
     * @throws UnexpectedPageException
     */
    private function verifyRouteName(array $matchedRoute, string $url): void
    {
        if ($matchedRoute['_route'] !== $this->getRouteName()) {
            throw new UnexpectedPageException(
                sprintf(
                    "Matched route '%s' does not match the expected route '%s' for URL '%s'",
                    $matchedRoute['_route'],
                    $this->getRouteName(),
                    $url
                )
            );
        }
    }

    /**
     * @throws UnexpectedPageException
     */
    private function verifyRouteParameters(array $requiredUrlParameters, array $matchedRoute): void
    {
        foreach ($requiredUrlParameters as $key => $value) {
            if (!isset($matchedRoute[$key]) || $matchedRoute[$key] !== $value) {
                throw new UnexpectedPageException(
                    sprintf(
                        "Matched route does not match the expected parameter '%s'='%s' (%s found)",
                        $key,
                        $value,
                        $matchedRoute[$key] ?? 'null'
                    )
                );
            }
        }
    }
}
