<?php

declare(strict_types=1);

namespace Tests\FriendsOfBehat\PageObjectExtension\Behat\Context;

use Behat\Behat\Context\Context;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

final class TestContext implements Context
{
    private static string $workingDir;

    private static Filesystem $filesystem;

    private static string $phpBin;

    private ?Process $process = null;

    private array $mergedConfig = [];

    #[\Behat\Hook\BeforeFeature]
    public static function beforeFeature(): void
    {
        self::$workingDir = sprintf('%s/%s/', sys_get_temp_dir(), uniqid('', true));
        self::$filesystem = new Filesystem();
        self::$phpBin = self::findPhpBinary();
    }

    #[\Behat\Hook\BeforeScenario]
    public function beforeScenario(): void
    {
        self::$filesystem->remove(self::$workingDir);
        self::$filesystem->mkdir(self::$workingDir, 0777);
        $this->mergedConfig = [];
    }

    #[\Behat\Hook\AfterScenario]
    public function afterScenario(): void
    {
        self::$filesystem->remove(self::$workingDir);
    }

    #[\Behat\Step\Given('a standard autoloader configured')]
    public function standardAutoloaderConfigured(): void
    {
        $this->thereIsFile('vendor/autoload.php', sprintf(
            <<<'PHP'
<?php

declare(strict_types=1);

$loader = require '%s';
$loader->addPsr4('App\\Tests\\', __DIR__ . '/../tests/');

return $loader;
PHP,
            __DIR__ . '/../../../vendor/autoload.php',
        ));
    }

    #[\Behat\Step\Given('a fake Mink driver available')]
    public function aFakeMinkDriverAvailable(): void
    {
        $this->thereIsFile('tests/FakeDriver.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Tests;

use Behat\Mink\Driver\CoreDriver;

final class FakeDriver extends CoreDriver
{
    private string $currentUrl = '';

    private int $statusCode = 200;

    public function __construct(private readonly array $pages = [])
    {
    }

    public function start(): void {}

    public function isStarted(): bool
    {
        return true;
    }

    public function stop(): void {}

    public function reset(): void
    {
        $this->currentUrl = '';
        $this->statusCode = 200;
    }

    public function visit($url): void
    {
        $this->currentUrl = $url;
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $this->statusCode = array_key_exists($path, $this->pages) ? 200 : 404;
    }

    public function getCurrentUrl(): string
    {
        return $this->currentUrl;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        $path = parse_url($this->currentUrl, PHP_URL_PATH) ?? '/';

        return $this->pages[$path] ?? '';
    }

    protected function findElementXpaths($xpath): array
    {
        $content = $this->getContent();
        if ($content === '') {
            return [];
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        $xpathObj = new \DOMXPath($dom);
        $nodes = $xpathObj->query($xpath);

        if ($nodes === false) {
            return [];
        }

        $results = [];
        foreach ($nodes as $i => $node) {
            $results[] = sprintf('(%s)[%d]', $xpath, $i + 1);
        }

        return $results;
    }

    public function getHtml($xpath): string
    {
        return '';
    }

    public function getText($xpath): string
    {
        return '';
    }

    public function getAttribute($xpath, $name): ?string
    {
        return null;
    }

    public function setValue($xpath, $value): void {}

    public function click($xpath): void {}

    public function isSelected($xpath): bool
    {
        return false;
    }

    public function isChecked($xpath): bool
    {
        return false;
    }

    public function isVisible($xpath): bool
    {
        return false;
    }

    public function getValue($xpath): string|bool|array|null
    {
        return null;
    }
}
PHP);
    }

    #[\Behat\Step\Given('/^a Behat configuration containing(?: "([^"]+)"|:)$/')]
    public function thereIsConfiguration(string $content): void
    {
        $this->mergedConfig = array_replace_recursive($this->mergedConfig, Yaml::parse($content));

        self::$filesystem->dumpFile(
            sprintf('%s/behat.dist.php', self::$workingDir),
            sprintf(
                "<?php\nreturn new \\Tests\\FriendsOfBehat\\PageObjectExtension\\Behat\\Config\\ArrayConfig(%s);\n",
                var_export($this->mergedConfig, true),
            ),
        );
    }

    #[\Behat\Step\Given('/^a (?:.+ |)file "([^"]+)" containing(?: "([^"]+)"|:)$/')]
    public function thereIsFile(string $file, string $content): string
    {
        $path = self::$workingDir . '/' . $file;

        if (str_ends_with($file, '.php') && str_contains($content, '* @')) {
            $content = $this->replaceAnnotationsWithAttributes($content);
        }

        self::$filesystem->dumpFile($path, $content);

        return $path;
    }

    #[\Behat\Step\Given('/^a feature file containing(?: "([^"]+)"|:)$/')]
    public function thereIsFeatureFile(string $content): void
    {
        $this->thereIsFile(sprintf('features/%s.feature', uniqid('', true)), $content);
    }

    #[\Behat\Step\When('/^I run Behat$/')]
    public function iRunBehat(): void
    {
        $executablePath = BEHAT_BIN_PATH;

        $this->process = new Process(
            [self::$phpBin, $executablePath, '--strict', '-vvv', '--no-interaction', '--lang=en'],
            self::$workingDir,
        );
        $this->process->start();
        $this->process->wait();
    }

    #[\Behat\Step\Then('/^it should pass$/')]
    public function itShouldPass(): void
    {
        if (0 === $this->getProcessExitCode()) {
            return;
        }

        throw new \DomainException(
            'Behat was expecting to pass, but failed with the following output:' . \PHP_EOL . \PHP_EOL . $this->getProcessOutput(),
        );
    }

    #[\Behat\Step\Then('/^it should pass with(?: "([^"]+)"|:)$/')]
    public function itShouldPassWith(string $expectedOutput): void
    {
        $this->itShouldPass();
        $this->assertOutputMatches($expectedOutput);
    }

    #[\Behat\Step\Then('/^it should fail$/')]
    public function itShouldFail(): void
    {
        if (0 !== $this->getProcessExitCode()) {
            return;
        }

        throw new \DomainException(
            'Behat was expecting to fail, but passed with the following output:' . \PHP_EOL . \PHP_EOL . $this->getProcessOutput(),
        );
    }

    #[\Behat\Step\Then('/^it should fail with(?: "([^"]+)"|:)$/')]
    public function itShouldFailWith(string $expectedOutput): void
    {
        $this->itShouldFail();
        $this->assertOutputMatches($expectedOutput);
    }

    #[\Behat\Step\Then('/^it should end with(?: "([^"]+)"|:)$/')]
    public function itShouldEndWith(string $expectedOutput): void
    {
        $this->assertOutputMatches($expectedOutput);
    }

    private function assertOutputMatches(string $expectedOutput): void
    {
        $output = $this->getProcessOutput();

        if (!preg_match('/' . preg_quote($expectedOutput, '/') . '/sm', $output)) {
            throw new \DomainException(sprintf(
                'Expected output to contain "%s", got:' . \PHP_EOL . \PHP_EOL . '%s',
                $expectedOutput,
                $output,
            ));
        }
    }

    private function getProcessOutput(): string
    {
        $this->assertProcessIsAvailable();

        return $this->process->getErrorOutput() . $this->process->getOutput();
    }

    private function getProcessExitCode(): int
    {
        $this->assertProcessIsAvailable();

        return $this->process->getExitCode();
    }

    private function assertProcessIsAvailable(): void
    {
        if (null === $this->process) {
            throw new \BadMethodCallException('Behat process cannot be found. Did you run it before making assertions?');
        }
    }

    private function replaceAnnotationsWithAttributes(string $code): string
    {
        return (string) preg_replace_callback(
            '/^( *)\/\*\*\s*@(Given|When|Then|BeforeScenario|AfterScenario|BeforeFeature|AfterFeature)(?:\s+(.+?))?\s*\*\/$/m',
            static function (array $m): string {
                $indent = $m[1];
                $name = $m[2];
                $arg = isset($m[3]) && $m[3] !== '' ? "('" . str_replace("'", "\\'", $m[3]) . "')" : '';
                $ns = in_array($name, ['Given', 'When', 'Then'], true) ? 'Step' : 'Hook';

                return "{$indent}#[\\Behat\\{$ns}\\{$name}{$arg}]";
            },
            $code,
        );
    }

    private static function findPhpBinary(): string
    {
        $phpBinary = (new PhpExecutableFinder())->find();
        if (false === $phpBinary) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        return $phpBinary;
    }
}
