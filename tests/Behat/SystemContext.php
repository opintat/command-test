<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

class SystemContext implements Context
{
    private KernelInterface $kernel;
    private Application $application;
    private BufferedOutput $output;
    private ?int $statusCode;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->application = new Application($kernel);
        $this->output = new BufferedOutput();
    }

    /**
     * @Given I am the system
     */
    public function iAmTheSystem()
    {
        Assert::same('cli', php_sapi_name());
    }

    /**
     * @When command :name is executed
     */
    public function commandIsExecuted(string $name)
    {
        $input = new ArgvInput(['behat-test', $name]);
        $this->application->doRun($input, $this->output);
    }

    /**
     * @Then I want to see :text in command output
     */
    public function iWantToSeeInCommandOutput(string $text)
    {
        Assert::contains($this->output->fetch(), $text);
    }

    /**
     * @When command :name is executed with argument :argument
     */
    public function commandIsExecutedWithArgument(string $name, string $argument)
    {
        $input = new ArgvInput(['behat-test', $name, $argument]);
        $this->statusCode = $this->application->doRun($input, $this->output);
    }

    /**
     * @Then I Expect status code :code
     */
    public function iExpectStatusCode(int $code)
    {
        Assert::eq($this->statusCode, $code);
    }
}
