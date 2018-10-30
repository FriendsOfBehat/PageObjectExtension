<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Page;

use Behat\Mink\Session;
use Symfony\Component\Routing\RouterInterface;

abstract class SymfonyPage extends Page implements SymfonyPageInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var array
     */
    protected static $additionalParameters = ['_locale' => 'en_US'];

    /**
     * @param Session $session
     * @param array $parameters
     * @param RouterInterface $router
     */
    public function __construct(Session $session, array $parameters, RouterInterface $router)
    {
        parent::__construct($session, $parameters);

        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getRouteName(): string;

    /**
     * {@inheritdoc}
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

    /**
     * @param string $path
     *
     * @return string
     */
    final protected function makePathAbsolute(string $path): string
    {
        $baseUrl = rtrim($this->getParameter('base_url'), '/') . '/';

        return 0 !== strpos($path, 'http') ? $baseUrl . ltrim($path, '/') : $path;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
     * @param array $matchedRoute
     * @param string $url
     *
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
     * @param array $requiredUrlParameters
     * @param array $matchedRoute
     *
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
