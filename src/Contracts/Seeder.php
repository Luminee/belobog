<?php

namespace Luminee\Belobog\Contracts;

use Exception;
use Illuminate\Support\Facades\DB;

abstract class Seeder
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
     * @var string
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

    protected $timeFields = ['created_at', 'updated_at'];

    public function __construct() {}

    public function init($conn, $iteration)
    {
        $this->connection = DB::connection();
        $this->database = DB::getDatabaseName();
        $this->conn = $conn;
        $this->iteration = $iteration;
    }

    public function tableName()
    {
        return $this->table;
    }

    protected function checkColumns($attributes)
    {
        $column_map = $this->getColumnsInfomation();
        $columns = array_column($column_map, 'column_name');
        $checkTimestamp = count(array_diff($this->timeFields, $columns)) == 0;
        foreach ($column_map as $column) {
            $name = $column->column_name;
            if ($name == 'id' || isset($attributes[$name])) {
                continue;
            }
            if ($column->is_nullable == 'NO') {
                $this->fillAttributesIfNull($column, $attributes, $name);
            }
            if (in_array($name, $this->timeFields) && $checkTimestamp) {
                $attributes[$name] = date('Y-m-d H:i:s');
            }
        }
        $diff = array_diff(array_keys($attributes), $columns);
        if (!empty($diff)) {
            throw new Exception('Column [' . implode(', ', $diff) . '] not exists!');
        }
        return $attributes;
    }

    protected function fillAttributesIfNull($column, $attributes, $name)
    {
        if (!is_null($column->character_maximum_length)) {
            $attributes[$name] = '';
        }
        if (!is_null($column->numeric_precision)) {
            $attributes[$name] = 0;
        }
        if (!is_null($column->datetime_precision)) {
            $attributes[$name] = date('Y-m-d H:i:s');
        }
    }

    protected function getColumnsInfomation()
    {
        $table_columns = 'information_schema.COLUMNS';
        $raw = 'column_name, is_nullable, character_maximum_length, numeric_precision, datetime_precision';
        $columns = DB::table($table_columns)
            ->where('table_schema', $this->database)
            ->where('table_name', $this->table)
            ->selectRaw($raw)
            ->get();
        return !empty($columns) ? $columns->toArray() : [];
    }
}
