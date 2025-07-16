<?php

namespace Luminee\Belobog\Concerns;

use Illuminate\Support\Facades\DB;

trait ExtendColumn
{
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
