<?php

namespace Luminee\Belobog\Database;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
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

    protected function t_id($string)
    {
        return $this->integer($string . '_id');
    }

    protected function idx_id($string)
    {
        return $this->index($string . '_id');
    }

    protected function kv_pair($k_null = false, $v_null = false)
    {
        $key = $this->string('key');
        if ($k_null) $key->nullable();

        $value = $this->text('value');
        if ($v_null) $value->nullable();
    }

    protected function morphs($name, $index = true, $null = false)
    {
        list($id, $type) = ["{$name}_id", "{$name}_type"];
        $this->unsignedInteger($id);
        $col = $this->string($type);
        if ($null) $col->nullable();
        if ($index) $this->index([$id, $type]);
    }

    protected function sort()
    {
        return $this->decimal('sort', 15, 8);
    }

    protected function timestamps($useCurrent = false, $nullable = true, $on_update = false)
    {
        $created_at = $this->timestamp('created_at');
        if ($useCurrent) $created_at->useCurrent();
        if ($nullable) $created_at->nullable();

        $updated_at = $this->timestamp('updated_at');
        if ($on_update) {
            $updated_at->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $useCurrent = false;
        }
        if ($useCurrent) $updated_at->useCurrent();
        if ($nullable) $updated_at->nullable();
    }
}
