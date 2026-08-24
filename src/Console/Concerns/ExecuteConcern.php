<?php

namespace Luminee\Belobog\Console\Concerns;

use Illuminate\Support\Facades\DB;
use Luminee\Switcher\Switcher;

trait ExecuteConcern
{
    /**
     * @var Switcher
     */
    protected $switcher;

    /**
     * @var array
     */
    protected $executors = [];

    /**
     * @var string
     */
    protected $executor_dir;

    /**
     * @var string
     */
    protected $executor_namespace;

    /**
     * @var int
     */
    protected $batch;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var bool
     */
    protected $run = false;

    /**
     * @var bool
     */
    protected $print = false;

    /**
     * @var array
     */
    protected $configs = [
        'action' => '',
        'table_name' => '',
        'table_key' => '',
        'create_file_name' => '',
    ];

    /**
     * @param string $dir
     * @return string
     */
    protected function prepareDir($dir)
    {
        if ($this->argument('directory')) {
            $studlied = $this->studlyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $studlied);
        }
        return $dir;
    }

    protected function prepareRunAndPrint()
    {
        $this->run = $this->option('run');
        if ($this->print = ($this->option('print') || $this->option('pretty'))) {
            $this->run = false;
        }
    }

    /**
     * @param string $filePath
     * @return string|false|null
     */
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

    /**
     * @param string $conn
     * @return void
     */
    protected function prepareExecutorsTable($conn)
    {
        $this->createExecutorTable($conn);
        $this->batch = DB::table($this->configs['table_name'])->max('batch') + 1;
        array_map(function ($item) {
            $this->executors[$item->{$this->configs['table_key']}]['record'] = $item;
        }, DB::table($this->configs['table_name'])->get()->keyBy($this->configs['table_key'])->toArray());
    }

    /**
     * Create executor table.
     *
     * @param string $conn
     * @return bool
     */
    protected function createExecutorTable($conn)
    {
        if (!empty(DB::select("Show tables like '{$this->configs['table_name']}'"))) {
            return true;
        }
        $base_path = vendor('luminee/belobog')->base_path;
        $executor_file = realpath($base_path . '/database/' . $this->configs['create_file_name']);
        $class = require $executor_file;
        $class->init($conn, 0);
        $class->up();
        $class->build();
    }

    /**
     * @param callable $execute
     * @param array    $connections
     * @return void
     */
    protected function switcherRun($execute, $connections)
    {
        foreach ($connections as $conn) {
            $this->count = 0;
            $this->comment('======== Connection on [' . $conn . '] ========');
            $this->switcher->run(function () use ($execute, $conn) {
                $this->prepareExecutorsTable($conn);
                foreach ($this->executors as $executor => $item) {
                    $execute($executor, $item, $conn);
                }
            }, $conn);
            if ($this->run) {
                $this->count == 0 ?
                    $this->line("Nothing to {$this->configs['action']}.") :
                    $this->info("{$this->configs['action']} done!");
            }
        }
    }

    /**
     * Handle directories.
     *
     * @param string $dir
     * @return void
     */
    protected function handleDirectories($dir)
    {
        foreach (scandir($dir) as $file) {
            if (in_array($file, ['.', '..', '.gitkeep', '.gitignore'])) {
                continue;
            }

            if (is_dir($dir . '/' . $file)) {
                if ($this->option('deep')) {
                    $this->handleDirectories($dir . '/' . $file);
                }
            } else {
                $this->instanceExecutor($dir . '/' . $file);
            }
        }
    }

    /**
     * Get class for executor.
     * 
     * @param string $file
     * @return void
     */
    protected function instanceExecutor($file)
    {
        $classname = $this->getClassNameFromFile($file);
        if ($classname === false) {
            $this->error("Can not instance the class in file [$file]");
            return;
        }
        if ($classname === null) {
            $class = require $file;
        } else {
            $base_class_name = basename(str_replace('\\', '/', $classname));
            if ($this->option('class') && $base_class_name != $this->option('class')) {
                return;
            }
            if ($this->option('except') && $base_class_name == $this->option('except')) {
                return;
            }
            $class = new $classname();
        }
        if ($this->option('table') && $class->tableName() != $this->option('table')) {
            return;
        }
        $executor_file = basename($file, '.php');
        $this->executors[$executor_file]['class'] = $class;
        $this->executors[$executor_file]['file'] = $file;
        return;
    }

    /**
     * Record executor.
     * 
     * @param string     $class
     * @param mixed|null $record
     * @param int        $ite
     * @return void
     */
    protected function recordExecutor($class, $record, $ite)
    {
        $new = ($record->record ?? '') . $ite . ',' . date('Ymd_His') . ';';
        if (!empty($record)) {
            DB::table($this->configs['table_name'])->where($this->configs['table_key'], $class)
                ->update(['iteration' => $ite, 'record' => $new]);
        } else {
            DB::table($this->configs['table_name'])->insert([$this->configs['table_key'] => $class, 'iteration' => $ite, 'batch' => $this->batch, 'record' => $new]);
        }
    }
}
