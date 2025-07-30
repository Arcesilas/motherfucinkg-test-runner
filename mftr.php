<?php

declare(strict_types=1);

namespace Mftr;

use Throwable;

function runner()
{
    static $runner = null;
    return $runner ??= new class () {
        private int $testsPassed = 0;
        private int $testsFailed = 0;
        private int $assertionsPassed = 0;
        private int $assertionsFailed = 0;
        private int $totalAssertionsPassed = 0;
        private int $totalAssertionsFailed = 0;
        private array $beforeEach = [];
        private array $afterEach = [];
        private array $bootstrap = [];
        private array $teardown = [];
        private bool $bootstrapped = false;

        private function startTest(string $name)
        {
            $this->assertionsPassed = 0;
            $this->assertionsFailed = 0;
            echo "🧪 $name\n";
        }

        private function endTest()
        {
            $success = $this->assertionsFailed === 0;
            $success ? $this->testsPassed++ : $this->testsFailed++;

            printf(
                "%s (assertions: %s passed / %s total)\033[97m\n\n",
                $success ? "🎯\033[92m" : "🚫\033[91m",
                $this->assertionsPassed,
                $this->assertionsPassed + $this->assertionsFailed
            );
        }

        public function runTest(string $description, callable $callback)
        {
            if (!$this->bootstrapped) {
                array_map(fn($cb) => $cb(), $this->bootstrap);
                $this->bootstrapped = true;
            }
            $this->startTest($description);
            array_map(fn($cb) => $cb(), runner()->beforeEach);
            $callback();
            array_map(fn($cb) => $cb(), runner()->afterEach);
            $this->endTest();
        }

        public function assertionPassed()
        {
            $this->assertionsPassed++;
            $this->totalAssertionsPassed++;
        }

        public function assertionFailed()
        {
            $this->assertionsFailed++;
            $this->totalAssertionsFailed++;
        }

        public function beforeEach(callable ...$callback): void
        {
            array_push($this->beforeEach, ...$callback);
        }

        public function afterEach(callable ...$callback): void
        {
            array_push($this->afterEach, ...$callback);
        }

        public function bootstrap(callable ...$callback): void
        {
            array_push($this->bootstrap, ...$callback);
        }

        public function teardown(callable ...$callback): void
        {
            array_push($this->teardown, ...$callback);
        }

        public function shutdown(): void
        {
            array_map(fn($cb) => $cb(), $this->teardown);
            $this->summary();
        }
        public function summary(): void
        {
            $total = $this->testsPassed + $this->testsFailed;
            $label = $total > 1 ? 'tests' : 'test';
            $success = $this->testsFailed === 0;
            printf(
                "📃 %s%s $label (%s passed, %s failed)\033[97m\n",
                $success ? "\033[92m" : "\033[91m",
                $total,
                $this->testsPassed,
                $this->testsFailed);
        }

        public function resolveCallerLocation(array $trace): string {
            foreach ($trace as $key => $frame) {
                if (
                    ($frame['function'] ?? '') === 'runTest'
                    && str_starts_with($frame['class'] ?? '', 'class@anonymous')
                ) {
                    $fr = $trace[$key-2] ?? [];
                    return sprintf("%s:%s", $fr['file'] ?? 'unknown', $fr['line'] ?? '0');
                }
            }
            return "unknown:0";
        }

        public function printResult(string $message, bool $success, ?string $location = null, bool $assertionError = true): void
        {
            $message = $assertionError ? "\033[0m$message" : "$message\033[0m";
            printf(
                "   \033[%sm%s $message%s\n",
                $success ? "92" : "91",
                $assertionError ? ($success ? '✔' : '✘') : '⚠️',
                $location ? " (in $location)" : ""
            );
        }
    };
};

register_shutdown_function(fn() => runner()->shutdown());

function test(string $description, callable $callback) {
    runner()->runTest($description, $callback);
}

function assertThat(string $description, callable $assertion)
{
    try {
        if ($assertion()) {
            runner()->assertionPassed();
            runner()->printResult($description, true);
        } else {
            runner()->assertionFailed();
            $location = runner()->resolveCallerLocation(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            runner()->printResult($description, false, $location);
        }
    } catch (Throwable $e) {
        runner()->assertionFailed();
        $location = runner()->resolveCallerLocation(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        runner()->printResult($e->getMessage(), false, $location, false);
    }
}

function expectThrowable(string $throwable, string $description, callable $callback)
{
    static $article = fn(string $throwable) => in_array(strtolower($throwable[0]), ['a', 'e', 'i', 'o']) ? 'an' : 'a';

    try {
        $callback();
        runner()->assertionFailed();
        $location = runner()->resolveCallerLocation(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        runner()->printResult(sprintf(
            "Expected $description to throw %s '$throwable' throwable, got none",
                $article($throwable),
            ), false, $location);
    } catch (Throwable $e) {
        if ($e instanceof $throwable) {
            runner()->assertionPassed();
            runner()->printResult(sprintf("$description throws %s %s", $article($throwable), $throwable), true);
        } else {
            runner()->assertionFailed();
            $exceptionClass = get_class($e);
            $location = runner()->resolveCallerLocation(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            runner()->printResult(sprintf(
                "Expected $description to throw %s '$throwable', but %s '$exceptionClass' was thrown",
                $article($throwable),
                $article($exceptionClass)
            ), false, $location);
        }
    }
}
