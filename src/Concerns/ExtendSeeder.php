<?php

namespace Luminee\Belobog\Concerns;

use Exception;
use Illuminate\Support\Facades\DB;

trait ExtendSeeder
{
    public function create(array $attributes)
    {
        $attributes = $this->checkColumns($attributes);
        $result = DB::table($this->table)->insertGetId($attributes);
        if (!$result) {
            throw new Exception('Create record failed!');
        }
        return $result;
    }

    public function firstIdOrCreate(array $attributes, $fields = null)
    {
        $record = DB::table($this->table)->where($fields ?? $attributes)->first(['id']);
        if (!$record) {
            return $record->id;
        }
        return $this->create($attributes);
    }

    public function count()
    {
        return DB::table($this->table)->count();
    }

    public function isEmpty()
    {
        return $this->count() == 0;
    }

    public function insert($values)
    {
        return DB::table($this->table)->insert($values);
    }
}
