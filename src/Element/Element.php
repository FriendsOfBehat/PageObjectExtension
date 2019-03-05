<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Element;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;

abstract class Element
{
    /** @var Session */
    private $session;

    /** @var array */
    private $parameters;

    /** @var DocumentElement|null */
    private $document;

    /**
     * This css locator indicates the root of the element in the dom tree.
     *
     * @var string
     */
    protected $locator = 'body';

    /**
     * This list of css locators allows you to specify elements in the current element by name.
     * You can use this name to retrieve them from getElement and getElements.
     *
     * @var array [name => locator]
     */
    protected $innerElements = [];

    /**
     * @param array|\ArrayAccess $minkParameters
     */
    public function __construct(Session $session, $minkParameters = [])
    {
        if (!is_array($minkParameters) && !$minkParameters instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(sprintf(
                '"$parameters" passed to "%s" has to be an array or implement "%s".',
                self::class,
                \ArrayAccess::class
            ));
        }

        $this->session = $session;
        $this->parameters = $minkParameters;
    }

    /**
     * Get the selector of the root of the element in the dom tree.
     */
    public function getLocator(): string
    {
        return $this->locator;
    }

    /**
     * Finds first element with specified selector inside the current element.
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return NodeElement|null
     *
     * @see ElementInterface::findAll for the supported selectors
     */
    public function find($selector, $locator)
    {
        return $this->getElement($this->locator)->find($selector, $locator);
    }

    /**
     * Finds all elements with specified selector inside the current element.
     *
     * Valid selector engines are named, xpath, css, named_partial and named_exact.
     *
     * 'named' is a pseudo selector engine which prefers an exact match but
     * will return a partial match if no exact match is found.
     * 'xpath' is a pseudo selector engine supported by SelectorsHandler.
     *
     * More selector engines can be registered in the SelectorsHandler.
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return NodeElement[]
     *
     * @see NamedSelector for the locators supported by the named selectors
     */
    public function findAll($selector, $locator)
    {
        return $this->getElement($this->locator)->findAll($selector, $locator);
    }

    /**
     * Returns element text (inside tag).
     *
     * @return string
     */
    public function getText()
    {
        return $this->getElement($this->locator)->getText();
    }

    /**
     * Returns element inner html.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->getElement($this->locator)->getHtml();
    }

    public function click(): void
    {
        $this->getElement($this->locator)->click();
    }

    public function fillField(string $locator, string $value)
    {
        $this->getElement($this->locator)->fillField($locator, $value);
    }

    protected function getParameter(string $name): NodeElement
    {
        return $this->parameters[$name] ?? null;
    }

    final protected function getDefinedElements(): array
    {
        $elements = [$this->locator => $this->locator];
        foreach ($this->innerElements as $name => $locator) {
            $elements[$name] = $this->locator . ' ' . $locator;
        }

        return $elements;
    }

    /**
     * @return NodeElement[]
     */
    protected function getElements(string $locator): array
    {
        return $this->getDocument()->findAll('css', $this->getDefinedElements()[$locator]);
    }

    /**
     * @throws ElementNotFoundException
     */
    protected function getElement(string $name, array $parameters = []): NodeElement
    {
        $element = $this->createElement($name, $parameters);

        if (!$this->getDocument()->has('xpath', $element->getXpath())) {
            throw new ElementNotFoundException(
                $this->getSession(),
                sprintf('Element named "%s" with parameters %s', $name, implode(', ', $parameters)),
                'xpath',
                $element->getXpath()
            );
        }

        return $element;
    }

    protected function hasElement(string $name, array $parameters = []): bool
    {
        return $this->getDocument()->has('xpath', $this->createElement($name, $parameters)->getXpath());
    }

    protected function getSession(): Session
    {
        return $this->session;
    }

    protected function getDriver(): DriverInterface
    {
        return $this->session->getDriver();
    }

    protected function getDocument(): DocumentElement
    {
        if (null === $this->document) {
            $this->document = new DocumentElement($this->session);
        }

        return $this->document;
    }

    private function createElement(string $name, array $parameters = []): NodeElement
    {
        $definedElements = $this->getDefinedElements();

        if (!isset($definedElements[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a defined element with name "%s". The defined ones are: %s.',
                $name,
                implode(', ', array_keys($definedElements))
            ));
        }

        $elementSelector = $this->resolveParameters($name, $parameters, $definedElements);

        return new NodeElement(
            $this->getSelectorAsXpath($elementSelector, $this->session->getSelectorsHandler()),
            $this->session
        );
    }

    private function getSelectorAsXpath($selector, SelectorsHandler $selectorsHandler): string
    {
        $selectorType = is_array($selector) ? key($selector) : 'css';
        $locator = is_array($selector) ? $selector[$selectorType] : $selector;

        return $selectorsHandler->selectorToXpath($selectorType, $locator);
    }

    private function resolveParameters(string $name, array $parameters, array $definedElements): string
    {
        if (!is_array($definedElements[$name])) {
            return strtr($definedElements[$name], $parameters);
        }

        array_map(
            function ($definedElement) use ($parameters): string {
                return strtr($definedElement, $parameters);
            }, $definedElements[$name]
        );

        return $definedElements[$name];
    }
}
