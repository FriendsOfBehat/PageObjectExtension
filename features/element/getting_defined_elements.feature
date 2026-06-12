Feature: Getting defined elements from a page

    Background:
        Given a standard autoloader configured
        And a fake Mink driver available
        And a Behat configuration containing:
        """
        default:
            suites:
                default:
                    contexts:
                        - App\Tests\SomeContext
        """

    Scenario: Getting an element that exists returns a NodeElement
        And a feature file containing:
        """
        Feature:
            Scenario:
                Then I should be able to find the heading element
        """
        And a page file "tests/SomePage.php" containing:
        """
        <?php

        namespace App\Tests;

        use FriendsOfBehat\PageObjectExtension\Page\Page;
        use Behat\Mink\Element\NodeElement;

        final class SomePage extends Page {
            protected function getUrl(array $urlParameters = []): string {
                return 'http://localhost/';
            }

            protected function getDefinedElements(): array {
                return [
                    'heading' => 'h1',
                    'paragraph' => 'p',
                ];
            }

            public function hasHeading(): bool {
                return $this->hasElement('heading');
            }
        }
        """
        And a context file "tests/SomeContext.php" containing:
        """
        <?php

        namespace App\Tests;

        use Behat\Behat\Context\Context;
        use Behat\Mink\Session;

        final class SomeContext implements Context {
            private SomePage $page;

            public function __construct() {
                $session = new Session(new FakeDriver(['/' => '<html><body><h1>Hello</h1><p>World</p></body></html>']));
                $session->start();
                $this->page = new SomePage($session);
            }

            /** @Then I should be able to find the heading element */
            public function shouldFindHeadingElement(): void {
                $this->page->open();
                assert($this->page->hasHeading() === true, 'Expected heading element to be found');
            }
        }
        """
        When I run Behat
        Then it should pass

    Scenario: Accessing an element that is not defined throws an exception
        And a feature file containing:
        """
        Feature:
            Scenario:
                Then accessing an undefined element should fail with a clear message
        """
        And a page file "tests/SomePage.php" containing:
        """
        <?php

        namespace App\Tests;

        use FriendsOfBehat\PageObjectExtension\Page\Page;

        final class SomePage extends Page {
            protected function getUrl(array $urlParameters = []): string {
                return 'http://localhost/';
            }

            protected function getDefinedElements(): array {
                return ['heading' => 'h1'];
            }

            public function findUndefinedElement(): void {
                $this->getElement('footer');
            }
        }
        """
        And a context file "tests/SomeContext.php" containing:
        """
        <?php

        namespace App\Tests;

        use Behat\Behat\Context\Context;
        use Behat\Mink\Session;

        final class SomeContext implements Context {
            private SomePage $page;

            public function __construct() {
                $session = new Session(new FakeDriver(['/' => '<html><body><h1>Hello</h1></body></html>']));
                $session->start();
                $this->page = new SomePage($session);
            }

            /** @Then accessing an undefined element should fail with a clear message */
            public function accessingUndefinedElementShouldThrow(): void {
                $this->page->open();
                try {
                    $this->page->findUndefinedElement();
                    throw new \RuntimeException('Expected InvalidArgumentException was not thrown');
                } catch (\InvalidArgumentException $e) {
                    assert(false !== strpos($e->getMessage(), 'footer'), 'Expected error to mention element name');
                }
            }
        }
        """
        When I run Behat
        Then it should pass
