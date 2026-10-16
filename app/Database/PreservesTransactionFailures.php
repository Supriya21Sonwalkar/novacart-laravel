<?php

namespace App\Database;

use App\Support\SecondaryFailure;
use Closure;
use LogicException;
use SplObjectStorage;
use Throwable;

/** Safety hooks for Laravel 7.2's native transaction loop; no business logic here. */
trait PreservesTransactionFailures
{
    private $transactionScopes = [];
    private $unsafeTransactionFailures;

    public function transaction(Closure $callback, $attempts = 1)
    {
        $scope = (object) ['base' => $this->transactionLevel()];
        if ($scope->base === 0) {
            $this->unsafeTransactionFailures = new SplObjectStorage();
        }
        if ($scope->base > 0 && !$this->getQueryGrammar()->supportsSavepoints()) {
            throw new LogicException('Nested store operations require savepoint support.');
        }
        $this->transactionScopes[] = $scope;
        try {
            // Laravel owns begin, callback execution, commit, return values and the retry loop.
            return parent::transaction(function ($connection) use ($callback, $scope) {
                $result = $callback($connection);
                if ($this->transactionLevel() !== $scope->base + 1) {
                    throw new LogicException('The transaction callback changed its owned transaction scope.');
                }
                return $result;
            }, max(1, (int) $attempts));
        } catch (Throwable $original) {
            // Also cover a beganTransaction listener throwing after begin succeeds.
            if ($this->transactionLevel() > $scope->base) {
                $this->restoreTransactionScope($scope, $original);
            }
            throw $original;
        } finally {
            array_pop($this->transactionScopes);
            if (!$this->transactionScopes) {
                $this->unsafeTransactionFailures = null;
            }
        }
    }

    protected function handleTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        $scope = end($this->transactionScopes);
        $safe = $this->restoreTransactionScope($scope, $e);
        // Retry the entire outer operation; never replay a nested callback independently.
        if ($scope->base === 0 && $safe && $currentAttempt < $maxAttempts && $this->causedByConcurrencyError($e)) {
            return;
        }
        throw $e;
    }

    protected function handleCommitTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        $scope = end($this->transactionScopes);
        // Laravel decrements the level before firing committed listeners. That work is already committed.
        if ($this->transactionLevel() <= $scope->base) {
            throw $e;
        }
        $safe = $this->restoreTransactionScope($scope, $e);
        if ($scope->base === 0 && $safe && $currentAttempt < $maxAttempts && $this->causedByConcurrencyError($e)) {
            return;
        }
        throw $e;
    }

    private function restoreTransactionScope($scope, Throwable $original)
    {
        if ($this->unsafeTransactionFailures && $this->unsafeTransactionFailures->contains($original)) {
            return false;
        }
        try {
            if ($this->transactionLevel() > $scope->base) {
                if ($this->getPdo()->inTransaction()) {
                    $this->rollBack($scope->base);
                } else {
                    // MySQL can abort every savepoint during a deadlock; reset stale bookkeeping.
                    $this->disconnect();
                }
            }
            return true;
        } catch (Throwable $rollbackFailure) {
            if (!$this->unsafeTransactionFailures) {
                $this->unsafeTransactionFailures = new SplObjectStorage();
            }
            $this->unsafeTransactionFailures->attach($original);
            SecondaryFailure::report('Database transaction rollback failed', $original, $rollbackFailure);
            try {
                $this->disconnect();
            } catch (Throwable $ignored) {
                // Preserve the original failure, including when connection cleanup fails.
            }
            return false;
        }
    }
}
