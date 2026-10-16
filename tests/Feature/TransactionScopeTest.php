<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use App\Database\SQLiteConnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDO;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class TransactionScopeTest extends TestCase
{
    private function connection()
    {
        $c = new FaultConnection(new PDO('sqlite::memory:'));
        $c->statement('CREATE TABLE probe (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $c;
    }

    public function test_db_facade_uses_registered_connection_and_preserves_results_and_rollback()
    {
        $this->assertInstanceOf(SQLiteConnection::class, DB::connection());
        DB::statement('CREATE TABLE facade_probe (name TEXT)');
        $result = DB::transaction(function ($db) {
            $db->table('facade_probe')->insert(['name' => 'kept']);
            return ['saved' => true];
        }, 3);
        $this->assertSame(['saved' => true], $result);
        $original = new \TypeError('facade failure');
        try {
            DB::transaction(function () use ($original) {
                DB::table('facade_probe')->insert(['name' => 'rolled back']);
                throw $original;
            });
            $this->fail('Expected original failure');
        } catch (Throwable $e) {
            $this->assertSame($original, $e);
        }
        $this->assertSame(['kept'], DB::table('facade_probe')->pluck('name')->all());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_named_db_connection_uses_the_same_failure_guards()
    {
        config(['database.connections.transaction_probe' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        $named = DB::connection('transaction_probe');
        $this->assertInstanceOf(SQLiteConnection::class, $named);
        $this->assertNotSame(DB::connection(), $named);
        $value = DB::connection('transaction_probe')->transaction(function ($db) use ($named) {
            $this->assertSame($named, $db);
            return 42;
        });
        $this->assertSame(42, $value);
        $this->assertSame(0, $named->transactionLevel());
    }

    public function test_mysql_factory_registers_guards_without_connecting_to_the_store()
    {
        $factory = new \Illuminate\Database\Connectors\ConnectionFactory(app());
        // The factory creates a lazy connector; no MySQL connection or store query occurs here.
        $connection = $factory->make([
            'driver' => 'mysql', 'host' => '127.0.0.1', 'database' => 'not_connected',
            'username' => 'not_connected', 'password' => '', 'prefix' => '',
        ]);
        $this->assertInstanceOf(\App\Database\MySqlConnection::class, $connection);
        $this->assertSame('mysql', $connection->getDriverName());
    }

    public function test_db_facade_begin_listener_failure_closes_owned_transaction_without_running_callback()
    {
        $original = new RuntimeException('begin listener failure');
        $called = false;
        app('events')->listen(\Illuminate\Database\Events\TransactionBeginning::class, function () use ($original) {
            throw $original;
        });
        try {
            DB::transaction(function () use (&$called) {
                $called = true;
            }, 3);
            $this->fail('Expected begin listener failure');
        } catch (Throwable $e) {
            $this->assertSame($original, $e);
        }
        $this->assertFalse($called);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DB::connection()->getPdo()->inTransaction());
    }

    public function test_db_facade_commit_listener_failure_is_not_replayed()
    {
        DB::statement('CREATE TABLE commit_probe (name TEXT)');
        $original = new RuntimeException('Deadlock found when trying to get lock');
        $calls = 0;
        app('events')->listen(\Illuminate\Database\Events\TransactionCommitted::class, function () use ($original) {
            throw $original;
        });
        try {
            DB::transaction(function () use (&$calls) {
                $calls++;
                DB::table('commit_probe')->insert(['name' => 'saved once']);
            }, 3);
            $this->fail('Expected commit listener failure');
        } catch (Throwable $e) {
            $this->assertSame($original, $e);
        }
        $this->assertSame(1, $calls);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(1, DB::table('commit_probe')->count());
    }

    public function test_success_returns_result_and_commits()
    {
        $c = $this->connection();
        $value = $c->transaction(function ($db) {
            $db->table('probe')->insert(['name' => 'saved']);
            return 'result';
        }, 1);
        $this->assertSame('result', $value);
        $this->assertSame(0, $c->transactionLevel());
        $this->assertSame(1, $c->table('probe')->count());
    }

    public function test_throwable_rolls_back_and_preserves_original()
    {
        $c = $this->connection();
        $original = new \TypeError('original failure');
        try {
            $c->transaction(function ($db) use ($original) {
                $db->table('probe')->insert(['name' => 'undo']);
                throw $original;
            }, 1);
            $this->fail('Expected failure');
        } catch (Throwable $e) {
            $this->assertSame($original, $e);
        }
        $this->assertSame(0, $c->transactionLevel());
        $this->assertSame(0, $c->table('probe')->count());
    }

    public function test_inner_failure_does_not_rollback_callers_work()
    {
        $c = $this->connection();
        $c->beginTransaction();
        $c->table('probe')->insert(['name' => 'outer']);
        try {
            $c->transaction(function ($db) {
                $db->table('probe')->insert(['name' => 'inner']);
                throw new RuntimeException('fail');
            }, 1);
        } catch (RuntimeException $e) {
        }
        $this->assertSame(1, $c->transactionLevel());
        $this->assertSame(['outer'], $c->table('probe')->pluck('name')->all());
        $c->commit();
    }

    public function test_inner_success_does_not_commit_callers_transaction()
    {
        $c = $this->connection();
        $c->beginTransaction();
        $c->transaction(function ($db) {
            $db->table('probe')->insert(['name' => 'inner']);
        }, 1);
        $this->assertSame(1, $c->transactionLevel());
        $c->rollBack();
        $this->assertSame(0, $c->table('probe')->count());
    }

    public function test_failed_begin_does_not_rollback_outer_transaction()
    {
        $c = $this->connection();
        $c->beginTransaction();
        $c->table('probe')->insert(['name' => 'outer']);
        $c->beginFailure = new RuntimeException('cannot create savepoint');
        try {
            $c->transaction(function () {
            }, 1);
            $this->fail('Expected begin failure');
        } catch (RuntimeException $e) {
            $this->assertSame($c->beginFailure, $e);
        }
        $this->assertSame(0, $c->rollbackCalls);
        $this->assertSame(1, $c->transactionLevel());
        $c->beginFailure = null;
        $c->rollBack();
    }

    public function test_commit_failure_rolls_back_owned_work()
    {
        $c = $this->connection();
        $c->commitFailure = new RuntimeException('commit failed');
        try {
            $c->transaction(function ($db) {
                $db->table('probe')->insert(['name' => 'undo']);
            }, 1);
            $this->fail('Expected commit failure');
        } catch (RuntimeException $e) {
            $this->assertSame($c->commitFailure, $e);
        }
        $this->assertSame(1, $c->rollbackCalls);
        $this->assertSame(0, $c->table('probe')->count());
    }

    public function test_exception_after_commit_does_not_rollback_or_retry()
    {
        $c = $this->connection();
        $c->afterCommitFailure = new RuntimeException('Deadlock found when trying to get lock');
        $calls = 0;
        try {
            $c->transaction(function ($db) use (&$calls) {
                $calls++;
                $db->table('probe')->insert(['name' => 'committed']);
            }, 3);
        } catch (RuntimeException $e) {
            $this->assertSame($c->afterCommitFailure, $e);
        }
        $this->assertSame(1, $calls);
        $this->assertSame(0, $c->rollbackCalls);
        $this->assertSame(1, $c->table('probe')->count());
    }

    public function test_rollback_and_logging_failure_do_not_hide_original()
    {
        $c = $this->connection();
        $c->rollbackFailure = new RuntimeException('rollback failed');
        $original = new RuntimeException('business failure');
        Log::shouldReceive('warning')->once()->andThrow(new RuntimeException('log unavailable'));
        try {
            $c->transaction(function () use ($original) {
                throw $original;
            }, 3);
            $this->fail('Expected failure');
        } catch (RuntimeException $e) {
            $this->assertSame($original, $e);
        }
        $this->assertSame(1, $c->rollbackCalls);
        $this->assertSame(0, $c->transactionLevel());
    }

    public function test_deadlock_retries_whole_operation_without_duplicate_writes()
    {
        $c = $this->connection();
        $calls = 0;
        $c->transaction(function ($db) use (&$calls) {
            $calls++;
            $db->table('probe')->insert(['name' => 'attempt']);
            if ($calls < 3) {
                throw new RuntimeException('Deadlock found when trying to get lock');
            }
        }, 3);
        $this->assertSame(3, $calls);
        $this->assertSame(2, $c->rollbackCalls);
        $this->assertSame(1, $c->table('probe')->count());
    }

    public function test_nested_deadlock_bubbles_to_outer_retry_owner()
    {
        $c = $this->connection();
        $outer = 0;
        $inner = 0;
        $c->transaction(function ($db) use (&$outer, &$inner, $c) {
            $outer++;
            $db->table('probe')->insert(['name' => 'outer']);
            $c->transaction(function ($db) use (&$inner) {
                $inner++;
                $db->table('probe')->insert(['name' => 'inner']);
                if ($inner === 1) {
                    throw new RuntimeException('Deadlock found when trying to get lock');
                }
            }, 3);
        }, 3);
        $this->assertSame(2, $outer);
        $this->assertSame(2, $inner);
        $this->assertSame(['outer', 'inner'], $c->table('probe')->pluck('name')->all());
    }

    public function test_nested_server_deadlock_retries_outer_after_database_aborts_every_scope()
    {
        $c = $this->connection();
        $c->preserveConnectionOnDisconnect = true;
        $calls = 0;
        $c->transaction(function ($db) use ($c, &$calls) {
            $calls++;
            $db->table('probe')->insert(['name' => 'outer']);
            $c->transaction(function ($db) use (&$calls) {
                $db->table('probe')->insert(['name' => 'inner']);
                if ($calls === 1) {
                    $db->getPdo()->rollBack();
                    throw new RuntimeException('Deadlock found when trying to get lock');
                }
            }, 3);
        }, 3);
        $this->assertSame(2, $calls);
        $this->assertSame(['outer', 'inner'], $c->table('probe')->pluck('name')->all());
    }

    public function test_nested_failed_rollback_prevents_outer_retry()
    {
        $c = $this->connection();
        $c->rollbackFailure = new RuntimeException('rollback unavailable');
        $original = new RuntimeException('Deadlock found when trying to get lock');
        $calls = 0;
        Log::shouldReceive('warning')->once();
        try {
            $c->transaction(function () use ($c, $original, &$calls) {
                $calls++;
                $c->transaction(function () use ($original) {
                    throw $original;
                }, 3);
            }, 3);
        } catch (RuntimeException $e) {
            $this->assertSame($original, $e);
        }
        $this->assertSame(1, $calls);
    }

    public function test_database_error_reporting_does_not_log_query_bindings_or_driver_secrets()
    {
        $failure = new \Illuminate\Database\QueryException('insert into payments (token) values (?)', ['SECRET-CREDENTIAL'], new \PDOException('Driver detail SECRET-CREDENTIAL'));
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            $this->assertSame('Database operation failed', $message);
            $this->assertNotEmpty($context['error_reference']);
            $this->assertArrayHasKey('frames', $context);
            $this->assertStringNotContainsString('SECRET-CREDENTIAL', json_encode($context));
            return true;
        });
        app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->report($failure);
    }

    public function test_production_error_response_hides_details_but_keeps_expected_http_errors()
    {
        config(['app.debug' => false]);
        \Illuminate\Support\Facades\Route::get('/test-transaction-failure', function () {
            throw new RuntimeException('SECRET DATABASE DETAILS');
        });
        \Illuminate\Support\Facades\Route::get('/test-validation-failure', function () {
            throw ValidationException::withMessages(['name' => 'Required name']);
        });
        \Illuminate\Support\Facades\Route::get('/test-forbidden', function () {
            abort(403);
        });
        \Illuminate\Support\Facades\Route::get('/test-missing', function () {
            abort(404);
        });
        $this->get('/test-transaction-failure')->assertStatus(500)->assertSee('Reference:')->assertDontSee('SECRET DATABASE DETAILS');
        $this->getJson('/test-transaction-failure')->assertStatus(500)->assertJsonStructure(['message', 'reference'])->assertDontSee('SECRET DATABASE DETAILS');
        $this->getJson('/test-validation-failure')->assertStatus(422)->assertJsonValidationErrors('name');
        $this->getJson('/test-forbidden')->assertStatus(403);
        $this->getJson('/test-missing')->assertStatus(404);
    }
}

class FaultConnection extends SQLiteConnection
{
    public $preserveConnectionOnDisconnect = false;

    public function disconnect()
    {
        if ($this->preserveConnectionOnDisconnect) {
            $this->setPdo($this->getPdo());
            return;
        }
        parent::disconnect();
    }
    public $beginFailure;
    public $commitFailure;
    public $afterCommitFailure;
    public $rollbackFailure;
    public $rollbackCalls = 0;

    public function beginTransaction()
    {
        if ($this->beginFailure) {
            throw $this->beginFailure;
        }
        parent::beginTransaction();
    }

    public function commit()
    {
        if ($this->commitFailure) {
            throw $this->commitFailure;
        }
        parent::commit();
        if ($this->afterCommitFailure) {
            throw $this->afterCommitFailure;
        }
    }

    public function rollBack($toLevel = null)
    {
        $this->rollbackCalls++;
        if ($this->rollbackFailure) {
            throw $this->rollbackFailure;
        }
        parent::rollBack($toLevel);
    }
}
