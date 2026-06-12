Feature: Checking if a page is open

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
        And a page file "tests/Homepage.php" containing:
        """
        <?php

        namespace App\Tests;

        use FriendsOfBehat\PageObjectExtension\Page\Page;

        final class Homepage extends Page {
            protected function getUrl(array $urlParameters = []): string {
                return 'http://localhost/';
            }
        }
        """

    Scenario: isOpen returns true when on the correct page
        And a feature file containing:
        """
        Feature:
            Scenario:
                Then the homepage should be open after opening it
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
                $session = new Session(new FakeDriver(['/' => '<html><body>Home</body></html>']));
                $session->start();
                $this->page = new Homepage($session);
            }

            /** @Then the homepage should be open after opening it */
            public function homepageShouldBeOpen(): void {
                $this->page->open();
                assert($this->page->isOpen() === true, 'Expected page to be open');
            }
        }
        """
        When I run Behat
        Then it should pass

    Scenario: isOpen returns false when on a different page
        And a feature file containing:
        """
        Feature:
            Scenario:
                Then the homepage should not be open before navigating to it
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
                $session = new Session(new FakeDriver(['/' => '<html><body>Home</body></html>', '/other' => '<html><body>Other</body></html>']));
                $session->start();
                $this->page = new Homepage($session);
            }

            /** @Then the homepage should not be open before navigating to it */
            public function homepageShouldNotBeOpenInitially(): void {
                assert($this->page->isOpen() === false, 'Expected page to not be open yet');
            }
        }
        """
        When I run Behat
        Then it should pass
