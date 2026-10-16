<?php

namespace App\Database;

class SQLiteConnection extends \Illuminate\Database\SQLiteConnection
{
    use PreservesTransactionFailures;
}
