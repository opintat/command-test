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
