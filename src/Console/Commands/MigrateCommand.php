<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Facades\DB;
use Luminee\Belobog\Console\Concerns\MigrateConcern;
use Luminee\Belobog\Database\Migration;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;
use Luminee\Switcher\Switcher;

class MigrateCommand extends Command
{
    use Directory, MigrateConcern;

    /**
     * @var Switcher
     */
    protected $switcher;

    /**
     * @var string
     */
    protected $migration_dir;

    /**
     * @var string
     */
    protected $migration_namespace;

    protected $migrations;

    protected $batch;

    protected $count;

    protected $run = false;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:migrate 
                            {directory? : Directory like project.module}
                            {--conn= : Connection for migrate, <see switcher>}
                            {--class= : Which class to run, if is use the namespace}
                            {--table= : Which table to run}
                            {--except= : Except class, if is use the namespace}
                            {--except-table= : Except table} 
                            {--deep : Migrate with sub directory migrations}
                            {--common=}
                            {--pretty}
                            {--print}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Database with project.module and connection';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->switcher = app('switcher');

        $this->bootDir();
    }

    protected function bootDir()
    {
        $this->migration_dir = realpath(config('belobog.migrations.dir'));
        $this->migration_namespace = config('belobog.migrations.namespace');
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws
     */
    public function handle()
    {
        $dir = $this->migration_dir;
        if ($this->argument('directory')) {
            $stulied = $this->stulyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $stulied);
        }

        $this->migrateDirectories($dir);

        $this->run = $this->option('run');
        if ($print = $this->option('print') || $this->option('pretty')) {
            $this->run = false;
        }

        foreach (explode(',', $this->option('conn') ?: DB::getDefaultConnection()) as $conn) {
            $this->count = 0;
            $this->comment('======== Connection on [' . $conn . '] ========');
            $this->switcher->run(function () use ($conn, $print) {
                $this->prepareMigrationsTable();
                foreach ($this->migrations as $migration => $item) {
                    if (empty($class = $item['class'] ?? null)) {
                        continue;
                    }
                    if (!($class instanceof Migration)) {
                        continue;
                    }
                    $record = $item['record'] ?? null;
                    $class->init($conn, $print ? 0 : ($record->iteration ?? 0));
                    $class->up();
                    $this->run ?
                        $this->migrate($class, $migration, $record) :
                        $this->print($class, $migration, $record);
                }
            }, $conn);
            if ($this->run) {
                $this->count == 0 ? $this->line("Nothing to migrate.") : $this->info("Migrate done!");
            }
        }
    }

    /**
     * Migrate directories.
     *
     * @param $dir
     */
    protected function migrateDirectories($dir)
    {
        foreach (scandir($dir) as $migration) {
            if (in_array($migration, ['.', '..', '.gitkeep', '.gitignore'])) {
                continue;
            }

            if (is_dir($dir . '/' . $migration)) {
                if ($this->option('deep')) {
                    $this->migrateDirectories($dir . '/' . $migration);
                }
            } else {
                $this->migrateClass($dir . '/' . $migration);
            }
        }
    }

    /**
     * Get class for migrate.
     * 
     * @param $file
     */
    protected function migrateClass($file)
    {
        $classname = $this->getClassNameFromFile($file);
        if ($classname === false) {
            $this->error("Can not instance the class in file [$file]");
            return;
        }
        if ($classname === null) {
            $class = require_once $file;
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
        $migrate_file = basename($file, '.php');
        $this->migrations[$migrate_file]['class'] = $class;
        return;
    }

    protected function print(Migration $class, $name, $record)
    {
        list($output, $pretty, $ite) = $class->prepare();
        if (($record->iteration ?? 0) >= $ite && !$this->option('print')) {
            return;
        }
        $this->comment($name . ' Sql: ');
        if (!$this->option('pretty')) {
            $this->line($output);
        } else {
            foreach ($pretty as $lines) {
                foreach ($lines as $line) {
                    $this->line($line);
                }
                $this->line('');
            }
        }
    }

    /**
     * @param Migration $class
     * @param $name
     * @param $record
     * @return void
     */
    protected function migrate(Migration $class, $name, $record)
    {
        $ite = $class->build();
        if (($record->iteration ?? 0) >= $ite) {
            $this->line("[$name] Has been migrate...");
            return;
        }
        $this->recordMigrate($name, $record, $ite);
        $this->info($name . ' Migrate.');
        $this->count++;
    }
}
