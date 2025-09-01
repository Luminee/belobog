<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Str;
use Luminee\Belobog\Console\Concerns\MakeConcern;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

class MakeMigrationCommand extends Command
{
    use Directory, MakeConcern;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:make:migration 
                            {directory : The directory of the migration, like project.module. If migration is not set, directory will be used as migration name} 
                            {migration? : The name of the migration, like create_users_table. If migration is not set, directory will be used as migration name} 
                            {--table= : The table name to migrate} 
                            {--a|anonymous : Make anonymous migration}
                            {--o|optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make migration to project.module direction';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->stub_dir = __DIR__ . '/../Stubs';

        $this->initFilesystem();

        $this->bootDir('migrations');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createMigration(...$this->prepareExecutor('migration'));
    }

    protected function createMigration($migration, $namespace, $path)
    {
        $class = Str::studly($migration);
        if ($this->checkFullClassExists($namespace, $class)) {
            return;
        }

        $stub = $this->getStubContent($namespace, 'migration');
        $search = ['{$namespace}', '{$class}', '{$table}'];
        $replace = [$namespace, $class, $this->getTable($migration)];
        $stub = str_replace($search, $replace, $stub);

        $this->makeExecutorFile($migration, $path, $stub);
    }

    protected function getTable($migration)
    {
        if ($this->option('table')) {
            return $this->option('table');
        }
        if (preg_match('/^(create|update)_(\w+)_table$/', $migration, $matches)) {
            return $matches[2];
        }
        return '';
    }
}
