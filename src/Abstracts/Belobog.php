<?php

namespace Luminee\Belobog\Abstracts;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

abstract class Belobog
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $database;

    /**
     * The database connection instance.
     * 
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $conn;

    /**
     * @var int
     */
    protected $iteration;

    /**
     * @var int
     */
    protected $localIteration = 0;

    /**
     * @var bool
     */
    protected $run = false;

    /**
     * @param string $conn
     * @param int    $iteration
     * @param bool   $run
     * @return void
     */
    public function init($conn, $iteration, $run = false)
    {
        $this->connection = DB::connection();
        $this->database = DB::getDatabaseName();
        $this->conn = $conn;
        $this->iteration = $iteration;
        $this->run = $run;
    }

    public function getIteration()
    {
        return $this->iteration;
    }

    public function getLocalIteration()
    {
        return $this->localIteration;
    }

    public function tableName()
    {
        return $this->table;
    }
}
