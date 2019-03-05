<?php

declare(strict_types=1);

namespace FriendsOfBehat\PageObjectExtension\Element;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;

/**
 * @method NodeElement|null find
 * @method NodeElement[] findAll
 * @method NodeElement|null findById
 * @method string getText
 * @method string getHtml
 * @method bool hasLink
 * @method NodeElement|null findLink
 * @method void clickLink
 * @method bool hasButton
 * @method NodeElement|null findButton
 * @method void pressButton
 * @method bool hasField
 * @method NodeElement|null findField
 * @method void fillField
 * @method bool hasCheckedField
 * @method bool hasUncheckedField
 * @method void checkField
 * @method void uncheckField
 * @method bool hasSelect
 * @method void selectFieldOption
 * @method bool hasTable
 * @method void attachFileToField
 * @method string getTagName
 * @method string|bool|array getValue
 * @method bool hasAttribute
 * @method string|null getAttribute
 * @method bool hasClass
 * @method void click
 * @method void press
 * @method void doubleClick
 * @method void rightClick
 * @method void check
 * @method void uncheck
 * @method bool isChecked
 * @method void selectOption
 * @method bool isSelected
 * @method void attachFile
 * @method bool isVisible
 * @method void mouseOver
 * @method void dragTo
 * @method void focus
 * @method void blur
 * @method void keyPress
 * @method void keyDown
 * @method void keyUp
 * @method void submit
 */
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

    public function containsText(string $text): bool
    {
        return strpos($this->getText(), $text) !== false;
    }

    public function __call($name, $params)
    {
        return $this->getElement($this->locator)->$name(...$params);
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
