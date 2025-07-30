# Motherfucking Test Runner

This is probably the tiniest test runner you've ever seen.  
I didn't think it was possible to have such a small working test runner — honestly. 

## License

This Test Runner is released under [WTF Public License](https://www.wtfpl.net/).

## Features

- One PHP file.
- No dependency (requires PHP>=7.4)
- Expressive API
- Extensible
- Pretty colored CLI output
- **Freedom:** use it the way you want, use it the way you need. No limits.

## Installation

### Via git clone

```shell
git clone https://github.com/Arcesilas/motherfucinkg-test-runner.git test-runner
```

### Via wget

```shell
wget -P ./tests https://raw.githubusercontent.com/Arcesilas/motherfucking-test-runner/refs/heads/main/test_functions.php
```

### Via Composer

lol. Seriously? For one file?

## Usage

1. Create a test file: `./tests/mf-feature.test.php`
2. Include MotherFucking Test Runner (and composer autoloader) in your test file:
```php
<?php

require dirname(__DIR__) . '/vendor/autolaod.php';
require 'mftr.php';

use function Mftr\{test, assertThat, expectThrowable}
```
3. Write your first test
```php
test('it does something cool', function() {
    assertThat('true is true', fn() => true === true);
})
```
4. Run your test file
```shell
php tests/mf-feature.test.php
```

## Configure the Runner

If you need to bootstrap the runner, you can register some callbacks to run at startup:

```php
use function Mftr\runner;

runner()->bootstrap(fn() => print("Let's make some motherfucking tests!"));
```

Feel free to pass multiple callbacks if you need.

In the same way, you can register some callbacks to run at shutdown:

```php
use function Mftr\runner;

runner()->shutdown(fn() => print("Wasn't that cool?"));
```

Of course, you can register some callbacks to run before or after each test:

```php
use function Mftr\runner;

runner()->beforeEach(fn() => print("Next test on the way"));
runner()->afterEach(fn() => print("THis test is done!"));
```

## Assertions

### AssertThat

Just provide `assertThat` with an expressive description and a callable that returns a boolean:

```php
test('The feature is working', function () {
    assertThat('true is true', fn() => true === true);
});
```

Run your test file:
```shell
🧪 The feature is working

🎯 (assertions: 1 passed / 1 total)
   ✔ true is true
📃 1 test (1 passed, 0 failed)
```

If it fails:
```shell
🧪 The feature is working
  ✘ Failed to assert that true is true (in /path/to/test-file.php:10)
🚫 (assertions: 0 passed / 1 total)

📃 1 test (0 passed, 1 failed)
```
The error message of failed assertions show the path of the file and the line (it may be clickable in your IDE).

### ExpectThrowable

You can test a `Throwable` is actually thrown:
```php
test('testing exceptions', function () {
    expectThrowable(Exception::class, 'it may fail', fn() => throw new Exception());
});
```
Result:
```shell
🧪 testing exceptions
  ✔ it may fail throws an Exception 
🎯 (assertions: 1 passed / 1 total)

📃 1 test (1 passed, 0 failed)
```

If a Throwable of an unexpected type is thrown:
```shell
🧪 testing exceptions
  ✘ Expected it may fail to throw an 'InvalidArgumentException', but an 'Exception' was thrown (in /path/fo/test-file.php:10)
🚫 (assertions: 0 passed / 1 total)

📃 1 test (0 passed, 1 failed)
```

If no `Throwable` is thrown:
```shell
🧪 testing exceptions
  ✘ Expected it may fail to throw an 'Exception' throwable, got none (in /path/to/test-file.php:10)
🚫 (assertions: 0 passed / 1 total)

📃 1 test (0 passed, 1 failed)
```

## Running tests

### Running a single test file

You may simply run your test file: `php tests/feature.test.php`

Obviously, you are free to use the filename of your choice: `feature.test.php`, `FeatureTest.php`, whatever. It's just a script you run.

### Running all test files in a directory

```php
#!/usr/bin/env php
<?php

require_once "tests/mftr.php";

foreach (glob(__DIR__ . '/tests/*.test.php') as $file) {
    require $file;
}
```
Do I really need to tell you not to forget to use `require_once` instead of `require` in your tests files if you want to be able to run them individually or all together?

## Extending MotherFucking Test Runner

If you're lazy (and you should be), you might want custom assertions.

Just create your own function, using `assertThat()` under the hood:

```php
function assertStringContains(string $haystack, string $needle) {
    assertThat("$haystack contains $needle", fn() => str_contains($haystack, $needle));
}
```

Then use it:

```php
test('testing custom assertion', function () {
    assertStringContains('This is a foobar string', 'bar');
    assertStringContains('foo', 'bar');
});
```

Result:
```shell
🧪 testing custom assertion
  ✔ This is a foobar string contains bar
  ✘ Failed to assert that foo contains bar
🚫 (assertions: 1 passed / 2 total)

📃 1 test (0 passed, 1 failed)
```

## Notes

If you want to run multiple test files at once and would like to know which file is being run, don't be shy and put a simple `echo basename(__FILE__) . PHP_EOL;` in your test file 😉.

You can even make it fancy:
```php
printf("📄 Tests file: %s\n", basename(__FILE__, '.test.php'));
```
