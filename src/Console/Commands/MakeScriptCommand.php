<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Str;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

class MakeScriptCommand extends Command
{
    use Directory;

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
     * @var string
     */
    protected $stub_dir;

    /**
     * @var string
     */
    protected $migration_dir;

    /**
     * @var string
     */
    protected $migration_namespace;

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
     * @return mixed
     */
    public function handle()
    {
        $dir = $this->migration_dir;
        $namespace = $this->migration_namespace;
        if ($migration = $this->argument('migration')) {
            $stulied = $this->stulyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $stulied);
            $this->makeDirectory($dir);

            if ($namespace) {
                $namespace .= '\\' . implode('\\', $stulied);
            }
        } else {
            $migration = $this->argument('directory');
        }

        $this->createMigration($migration, $namespace, $dir);
    }

    protected function createMigration($migration, $namespace, $path)
    {
        $class = Str::studly($migration);
        if ($namespace) {
            $full_class = $namespace . '\\' . $class;
            if (class_exists($full_class)) {
                $this->error("Class $full_class Has Exist!");
                return;
            }
        }

        $migration_dir = $namespace && !$this->option('anonymous') ? '' : '/anonymous';
        $stub_file = $this->stub_dir . $migration_dir . "/migration.stub";
        $stub = $this->files->get($stub_file);
        $search = ['{$namespace}', '{$class}', '{$table}'];
        $replace = [$namespace, $class, $this->getTable($migration)];
        $stub = str_replace($search, $replace, $stub);

        $name = date('Y_m_d_His') . '_' . $migration;
        $file = $path . '/' . $name . '.php';
        $this->files->put($file, $stub);
        if ($this->option('optimize')) {
            exec("composer -o dump");
        }

        $this->info("File $name.php Create Success! [in $path]");
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
