<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

class RetryHelper
{
    /**
     * @var int Max attempts to execute user-defined callback function ($action)
     */
    private int $defaultMaxAttempts = 10;

    /**
     * @var callable Callback function which decides need to do next attempt or not
     */
    private $isTemporaryException;

    /**
     * @var callable
     */
    private $delayBeforeNextAttempt;

    /**
     * @var callable
     */
    private $onAttemptsLimitReached;

    /**
     * @var callable
     */
    private $log;

    public function __construct()
    {
        $this->isTemporaryException = function(/* $e */) {
            return true;
        };
        $this->delayBeforeNextAttempt = function(int $attempt) {
            return mt_rand(0, $attempt * 10000 - 1) * 1000;
        };
        $this->onAttemptsLimitReached = function(Throwable $e, int $attempt) {
            throw new \Exception($e->getMessage() . " (attempt {$attempt})", $e->getCode(), $e);
        };
        $this->log = function(string $s) {
            echo "\nDEBUG: {$s}\n";
        };
    }

    /**
     * Executes some user-defined callback function $action 1 time if all is OK and several times (up to $maxAttempts)
     * if an exception is throwing until it will be executed without exception. When exception was thrown optional
     * callback function $isTemporaryException receives exception as an argument and returns TRUE if error is
     * temporary and this is reasonable to continue attempts. On false attempts will be stopped before $maxAttempts
     * limit reached.
     *
     * @param callable $action                      Callback function with main action to perform
     * @return mixed                                Result value is fully dependent on user-defined callback function
     * @throws Throwable
     */
    public function doSeveralAttempts(callable $action, int $maxAttempts = null): mixed
    {
        if (!$maxAttempts) {
            $maxAttempts = $this->defaultMaxAttempts;
        }
        $attempt = 0;
        do {
            $attempt++;
            if ($attempt > 1) {
                call_user_func($this->log, "Retrying, attempt #{$attempt}");
            }
            try {
                $result = $action();
                break;
            } catch (Throwable $e) {
                call_user_func($this->log, "Got Exception: {$e->getMessage()}");
                if ($attempt < $maxAttempts) {
                    if ($this->isTemporaryException === null || call_user_func($this->isTemporaryException, $e)) {
                        $delay_ms = call_user_func($this->delayBeforeNextAttempt, $attempt);
                        call_user_func($this->log, "Sleep " . number_format($delay_ms / 1000000, 2) . " seconds until next try");
                        usleep($delay_ms);
                        continue;
                    }
                }
                call_user_func($this->onAttemptsLimitReached, $e, $attempt);
                throw $e;
            }
        } while (true);
        return $result;
    }

    /**
     * @param int $defaultMaxAttempts
     * @return RetryHelper
     */
    public function setDefaultMaxAttempts(int $defaultMaxAttempts): RetryHelper
    {
        $this->defaultMaxAttempts = $defaultMaxAttempts;
        return $this;
    }

    /**
     * @param callable $isTemporaryException
     * @return RetryHelper
     */
    public function setIsTemporaryException(callable $isTemporaryException): RetryHelper
    {
        $this->isTemporaryException = $isTemporaryException;
        return $this;
    }

    /**
     * @param callable $delayBeforeNextAttempt
     * @return RetryHelper
     */
    public function setDelayBeforeNextAttempt(callable $delayBeforeNextAttempt): RetryHelper
    {
        $this->delayBeforeNextAttempt = $delayBeforeNextAttempt;
        return $this;
    }

    /**
     * @param callable $onAttemptsLimitReached
     * @return RetryHelper
     */
    public function setOnAttemptsLimitReached(callable $onAttemptsLimitReached): RetryHelper
    {
        $this->onAttemptsLimitReached = $onAttemptsLimitReached;
        return $this;
    }

    /**
     * @param callable $log
     * @return RetryHelper
     */
    public function setLog(callable $log): RetryHelper
    {
        $this->log = $log;
        return $this;
    }
}
