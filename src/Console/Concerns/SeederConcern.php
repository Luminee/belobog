<?php

namespace Luminee\Belobog\Console\Concerns;

use Illuminate\Support\Facades\DB;

trait SeederConcern
{
    protected function getClassNameFromFile($filePath)
    {
        $namespace = '';
        foreach (file($filePath, FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match('/namespace\s+([\w\\\]+)/', $line, $match)) {
                $namespace = $match[1];
            }
            if (preg_match('/return new class extends/', $line, $match)) {
                return null;
            }
            if (preg_match('/class\s+(\w+)/', $line, $match)) {
                return trim($namespace . '\\' . $match[1], '\\');
            }
        }
        return false;
    }

    protected function prepareSeederTable()
    {
        $this->createSeederTable();
        $this->batch = DB::table('seeders')->max('batch') + 1;
        array_map(function ($item) {
            $this->seeders[$item->seeder]['record'] = $item;
        }, DB::table('seeders')->get()->keyBy('seeder')->toArray());
    }

    /**
     * Create seeders table.
     *
     * @return bool
     */
    protected function createSeederTable()
    {
        if (!empty(DB::select("Show tables like 'seeders'"))) {
            return true;
        }
        $seeder_file = __DIR__ . '/../Database/create_seeders_table.php';
        $class = require_once $seeder_file;
        $class->up();
        $class->build();
    }

    /**
     * Record seeder.
     * 
     * @param $class
     * @param $record
     * @param $ite
     */
    protected function recordSeeder($class, $record, $ite)
    {
        $new = ($record->record ?? '') . $ite . ',' . date('Ymd_His') . ';';
        if (!empty($record)) {
            DB::table('seeders')->where('seeder', $class)
                ->update(['iteration' => $ite, 'record' => $new]);
        } else {
            DB::table('seeders')->insert(['seeder' => $class, 'iteration' => $ite, 'batch' => $this->batch, 'record' => $new]);
        }
    }
}
