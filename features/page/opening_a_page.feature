Feature: Opening a page

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

    Scenario: Opening a page navigates to the correct URL and content is readable
        And a feature file containing:
        """
        Feature:
            Scenario:
                When I open the homepage
                Then I should see "Welcome to the homepage"
        """
        And a context file "tests/SomeContext.php" containing:
        """
        <?php

        namespace App\Tests;

        use Behat\Behat\Context\Context;
        use Behat\Mink\Session;

        final class SomeContext implements Context {
            private Homepage $page;

            public function __construct() {
                $session = new Session(new FakeDriver(['/' => '<html><body><h1>Welcome to the homepage</h1></body></html>']));
                $session->start();
                $this->page = new Homepage($session);
            }

            /** @When I open the homepage */
            public function openHomepage(): void {
                $this->page->open();
            }

            /** @Then I should see :text */
            public function shouldSee(string $text): void {
                assert(false !== strpos($this->page->getContent(), $text), 'Text not found: ' . $text);
            }
        }

        final class Homepage extends \FriendsOfBehat\PageObjectExtension\Page\Page {
            public function getContent(): string {
                return $this->getDocument()->getContent();
            }

            protected function getUrl(array $urlParameters = []): string {
                return 'http://localhost/';
            }
        }
        """
        When I run Behat
        Then it should pass

    Scenario: Opening a page that returns an error status code raises an exception
        And a feature file containing:
        """
        Feature:
            Scenario:
                When I try to open a missing page
        """
        And a context file "tests/SomeContext.php" containing:
        """
        <?php

        namespace App\Tests;

        use Behat\Behat\Context\Context;
        use Behat\Mink\Session;
        use FriendsOfBehat\PageObjectExtension\Page\UnexpectedPageException;

        final class SomeContext implements Context {
            private NotFoundPage $page;

            public function __construct() {
                $session = new Session(new FakeDriver(['/' => '<html><body>Home</body></html>']));
                $session->start();
                $this->page = new NotFoundPage($session);
            }

            /** @When I try to open a missing page */
            public function openMissingPage(): void {
                $this->page->open();
            }
        }

        final class NotFoundPage extends \FriendsOfBehat\PageObjectExtension\Page\Page {
            protected function getUrl(array $urlParameters = []): string {
                return 'http://localhost/this-page-does-not-exist';
            }
        }
        """
        When I run Behat
        Then it should fail with "UnexpectedPageException"
