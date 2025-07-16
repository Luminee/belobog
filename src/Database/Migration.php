<?php

namespace Luminee\Belobog\Database;

use Illuminate\Database\Query\Expression;
use Luminee\Belobog\Concerns\ExtendColumn;
use Luminee\Belobog\Contracts\Migration as MigrationContract;

/**
 * Class Migration
 *
 * @method $this increments($column)
 * @method $this bigIncrements($column)
 * @method $this char($column, $length = null)
 * @method $this string($column, $length = null)
 * @method $this text($column)
 * @method $this longText($column)
 * @method $this json($column)
 * @method $this integer($column, $autoIncrement = false, $unsigned = false)
 * @method $this tinyInteger($column, $autoIncrement = false, $unsigned = false)
 * @method $this smallInteger($column, $autoIncrement = false, $unsigned = false)
 * @method $this bigInteger($column, $autoIncrement = false, $unsigned = false)
 * @method $this unsignedInteger($column, $autoIncrement = false)
 * @method $this unsignedBigInteger($column, $autoIncrement = false)
 * @method $this unsignedTinyInteger($column, $autoIncrement = false)
 * @method $this float($column, $total = 8, $places = 2)
 * @method $this decimal($column, $total = 8, $places = 2)
 * @method $this boolean($column)
 * @method $this morphs($name, $indexName = null)
 * @method $this date($column)
 * @method $this dateTime($column, $precision = 0)
 * @method $this time($column, $precision = 0)
 * @method $this timestamp($column, $precision = 0)
 * @method $this softDeletes($column = 'deleted_at', $precision = 0)
 * @method $this rememberToken()
 * @method $this renameColumn($from, $to)
 * @method $this dropColumn($columns)
 *
 * @method $this index($columns, $name = null, $algorithm = null)
 * @method $this primary($columns, $name = null, $algorithm = null)
 * @method $this unique($columns, $name = null, $algorithm = null)
 * @method $this dropIndex($index)
 * @method $this dropUnique($index)
 *
 * @method $this after($column)
 * @method $this change()
 * @method $this autoIncrement()
 * @method $this default($value)
 * @method $this nullable()
 * @method $this unsigned()
 * @method $this useCurrent()
 *
 * @see \Illuminate\Database\Schema\Blueprint
 * @package Migrations
 */
class Migration extends MigrationContract
{
    use ExtendColumn;

    /**
     * Run up the database migrations.
     *
     * @return void
     */
    public function up() {}

    /**
     * Run down the database migrations.
     *
     * @return void
     */
    public function down() {}

    protected function comment($string)
    {
        if ($this->conn == 'dev') {
            $this->column->comment($string);
        }
        return $this;
    }

    protected function raw($raw)
    {
        return new Expression($raw);
    }

    protected function bit0()
    {
        return $this->raw("b'0'");
    }

    protected function bit1()
    {
        return $this->raw("b'1'");
    }
}
