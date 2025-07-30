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
            echo "  \033[92m✔\033[97m $description\n";
        } else {
            runner()->assertionFailed();
            printf("  \033[91m✘\033[97m Failed to assert that %s\n", $description);
        }
    } catch (Throwable $e) {
        runner()->assertionFailed();
        printf("  \033[91m⚠️ %s\033[97m\n", $e->getMessage());
    }
}

function expectThrowable(string $throwable, string $description, callable $callback)
{
    $article = in_array(strtolower($throwable[0]), ['a', 'e', 'i', 'o']) ? 'an' : 'a';
    try {
        $callback();
        runner()->assertionFailed();
        echo "  \033[91m✘ Expected $description to throw $article '$throwable' throwable, got none\033[97m\n";
    } catch (Throwable $e) {
        if ($e instanceof $throwable) {
            runner()->assertionPassed();
            echo "  \033[92m✔\033[97m $description throws $article $throwable \n";
        } else {
            runner()->assertionFailed();
            $exceptionClass = get_class($e);
            $aOrAn = in_array(strtolower($exceptionClass[0]), ['a', 'e', 'i', 'o']) ? 'an' : 'a';
            echo "  \033[91m✘ Expected $description to throw $article '$throwable', but $aOrAn '$exceptionClass' was thrown\033[97m\n";
        }
    }
}
