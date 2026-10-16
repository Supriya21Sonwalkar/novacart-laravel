<?php

namespace App\Database;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    use PreservesTransactionFailures;
}
