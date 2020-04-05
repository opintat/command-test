# How to set up

Install symfony
```
symfony new command-test
cd command-test
```

Edit composer.json and set php version to ^7.4:
```
"require": {
    "php": "^7.4",
    "ext-ctype": "*",
    "ext-iconv": "*",
    "symfony/console": "5.0.*",
    "symfony/dotenv": "5.0.*",
    "symfony/flex": "^1.3.1",
    "symfony/framework-bundle": "5.0.*",
    "symfony/yaml": "5.0.*"
},
```

Install Behat and the Friends of Behat Symfony exttension:
```
composer require --dev behat/behat
composer require --dev friends-of-behat/symfony-extension:^2.0
```

Now run:
```
> vendor/bin/behat
Feature:
    In order to prove that the Behat Symfony extension is correctly installed
    As a user
    I want to have a demo scenario

  Scenario: It receives a response from Symfony's kernel # features/demo.feature:10
    When a demo scenario sends a request to "/"          # App\Tests\Behat\DemoContext::aDemoScenarioSendsARequestTo()
    Then the response should be received                 # App\Tests\Behat\DemoContext::theResponseShouldBeReceived()

1 scenario (1 passed)
2 steps (2 passed)
0m0.03s (23.08Mb)
```

Install maker bundle and Webmozart Assert library
```
composer require symfony/maker-bundle --dev
composer require --dev webmozart/assert
```

Add a new Context File tests/Behat/SystemContext.php
```
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
```

And add a behat feature in features/greetCommand.feature
```
Feature:
  In order to prove that the testCommand works as expected
  I am the system
  I want to use the command

  Scenario: Command is executed without errors
    Given I am the system
    When command "GreetCommand" is executed
    Then I want to see "Hello World" in command output

  Scenario: Command greets the argument
    Given I am the system
    When command "GreetCommand" is executed with argument "Oliver"
    Then I want to see "Hello Oliver" in command output

  Scenario: Command does not like Foo and Bar
    Given I am the system
    When command "GreetCommand" is executed with argument "Foo"
    Then I Expect status code "1"
```
Now execute behat tests:
```
⇒  vendor/bin/behat

Scenario: Command is executed without errors         # features/greetCommand.feature:6
    Given I am the system                              # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed            # App\Tests\Behat\SystemContext::commandIsExecuted()
      Command "GreetCommand" is not defined. (Symfony\Component\Console\Exception\CommandNotFoundException)

```

Test is failing, so create the command:
```
⇒  symfony console make:command GreetCommand
created: src/Command/GreetCommand.php
Success! 
```

Run the test again:
```
⇒  vendor/bin/behat                         
Feature:
  In order to prove that the testCommand works as expected
  I am the system
  I want to use the command

  Scenario: Command is executed without errors         # features/greetCommand.feature:6
    Given I am the system                              # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed            # App\Tests\Behat\SystemContext::commandIsExecuted()
    Then I want to see "Hello World" in command output # App\Tests\Behat\SystemContext::iWantToSeeInCommandOutput()
      Expected a value to contain "Hello World". Got: "
       [OK] You have a new command! Now make it your own! Pass --help to see your options.                                    
      
      " (InvalidArgumentException)
```

The test still fails, so change the execute function of the command:
```
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);
    
    $io->writeln('Hello World');

    return 0;
}
```

The first test passes, but not the second...
```
⇒  vendor/bin/behat 
...
Scenario: Command greets the argument                            # features/greetCommand.feature:11
    Given I am the system                                          # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed with argument "Oliver" # App\Tests\Behat\SystemContext::commandIsExecutedWithArgument()
    Then I want to see "Hello Oliver" in command output            # App\Tests\Behat\SystemContext::iWantToSeeInCommandOutput()
      Expected a value to contain "Hello Oliver". Got: "Hello World
      " (InvalidArgumentException)
```

So we need to change the execute and configure functions of the command:
```
protected function configure()
{
    $this
        ->setDescription('A greet command.')
        ->addArgument('name', InputArgument::OPTIONAL, 'Argument description')
    ;
}

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $name = $input->getArgument('name');

    if (!$name) {
        $name = 'World';
    }

    $io->writeln(sprintf('Hello %s', $name));

    return 0;
}
```

Run behat again:
```
⇒  vendor/bin/behat 
...
  Scenario: Command does not like Foo and Bar                   # features/greetCommand.feature:16
    Given I am the system                                       # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed with argument "Foo" # App\Tests\Behat\SystemContext::commandIsExecutedWithArgument()
    Then I Expect status code "1"                               # App\Tests\Behat\SystemContext::iExpectStatusCode()
      Expected a value equal to 1. Got: 0 (InvalidArgumentException)
```

Yeah, now the first two tests are passing, to make the last test pass change the execute function a last time:
```
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $name = $input->getArgument('name');

    if (in_array($name, ['Foo', 'Bar'])) {
        $io->error('I don\' like Foo and Bar!');

        return 1;
    }

    if (!$name) {
        $name = 'World';
    }

    $io->writeln(sprintf('Hello %s', $name));

    return 0;
}
```

Run behat again:
```
⇒  vendor/bin/behat 
Feature:
  In order to prove that the testCommand works as expected
  I am the system
  I want to use the command

  Scenario: Command is executed without errors         # features/greetCommand.feature:6
    Given I am the system                              # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed            # App\Tests\Behat\SystemContext::commandIsExecuted()
    Then I want to see "Hello World" in command output # App\Tests\Behat\SystemContext::iWantToSeeInCommandOutput()

  Scenario: Command greets the argument                            # features/greetCommand.feature:11
    Given I am the system                                          # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed with argument "Oliver" # App\Tests\Behat\SystemContext::commandIsExecutedWithArgument()
    Then I want to see "Hello Oliver" in command output            # App\Tests\Behat\SystemContext::iWantToSeeInCommandOutput()

  Scenario: Command does not like Foo and Bar                   # features/greetCommand.feature:16
    Given I am the system                                       # App\Tests\Behat\SystemContext::iAmTheSystem()
    When command "GreetCommand" is executed with argument "Foo" # App\Tests\Behat\SystemContext::commandIsExecutedWithArgument()
    Then I Expect status code "1"                               # App\Tests\Behat\SystemContext::iExpectStatusCode()

3 scenarios (3 passed)
9 steps (9 passed)
0m0.03s (12.43Mb)
```

Now everything passes!

The command is now tested with behat, congratulations!
