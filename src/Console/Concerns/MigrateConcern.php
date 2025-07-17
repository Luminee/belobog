<?php

namespace Luminee\Belobog\Console\Concerns;

use Illuminate\Support\Facades\DB;

trait MigrateConcern
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

    protected function prepareMigrationsTable()
    {
        $this->createMigrationsTable();
        $this->batch = DB::table('migrations')->max('batch') + 1;
        array_map(function ($item) {
            $this->migrations[$item->migration]['record'] = $item;
        }, DB::table('migrations')->get()->keyBy('migration')->toArray());
    }

    /**
     * Create migrations table.
     *
     * @return bool
     */
    protected function createMigrationsTable()
    {
        if (!empty(DB::select("Show tables like 'migrations'"))) {
            return true;
        }
        $migrate_file = __DIR__ . '/../Database/create_migrations_table.php';
        $class = require_once $migrate_file;
        $class->up();
        $class->build();
    }

    /**
     * Record migrate.
     * 
     * @param $class
     * @param $record
     * @param $ite
     */
    protected function recordMigrate($class, $record, $ite)
    {
        $new = ($record->record ?? '') . $ite . ',' . date('Ymd_His') . ';';
        if (!empty($record)) {
            DB::table('migrations')->where('migration', $class)
                ->update(['iteration' => $ite, 'record' => $new]);
        } else {
            DB::table('migrations')->insert(['migration' => $class, 'iteration' => $ite, 'batch' => $this->batch, 'record' => $new]);
        }
    }
}
