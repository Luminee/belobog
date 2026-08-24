<?php

namespace Luminee\Belobog\Abstracts;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;

abstract class Seeder extends Belobog
{
    /**
     * 是否使用迭代
     *
     * @var bool
     */
    protected $useIteration = false;

    protected $timeFields = ['created_at', 'updated_at'];

    public function __construct() {}

    public function isUseIteration()
    {
        return $this->useIteration;
    }

    protected function newIteration(?Closure $closure = null)
    {
        $this->localIteration++;
        if ($this->run && $closure && $this->localIteration > $this->iteration) {
            $closure();
        }
    }

    protected function checkColumns(array $attributes)
    {
        $column_map = $this->getColumnsInformation();
        $columns = array_column($column_map, 'COLUMN_NAME');
        $checkTimestamp = count(array_diff($this->timeFields, $columns)) == 0;
        foreach ($column_map as $column) {
            $name = $column->COLUMN_NAME;
            if ($name == 'id' || isset($attributes[$name])) {
                continue;
            }
            if ($column->IS_NULLABLE == 'NO') {
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

    protected function fillAttributesIfNull(\stdClass $column, array &$attributes, string $name)
    {
        if (!is_null($column->CHARACTER_MAXIMUM_LENGTH)) {
            $attributes[$name] = '';
        }
        if (!is_null($column->NUMERIC_PRECISION)) {
            $attributes[$name] = 0;
        }
        if (!is_null($column->DATETIME_PRECISION)) {
            $attributes[$name] = date('Y-m-d H:i:s');
        }
    }

    protected function getColumnsInformation()
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
