<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Str;
use Luminee\Belobog\Console\Concerns\MakeConcern;
use Luminee\Belobog\Enums\ExecutorEnum;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

/**
 * @author LuminEe
 */
class MakeSeederCommand extends Command
{
    use Directory, MakeConcern;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:make:seeder 
                            {directory : The directory of the seeder, like project.module. If seeder is not set, directory will be used as seeder name} 
                            {seeder? : The name of the seeder, like init_users_seeder. If seeder is not set, directory will be used as seeder name} 
                            {--table= : The table name to seed} 
                            {--a|anonymous : Make anonymous seeder}
                            {--o|optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make seeder to project.module direction';

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

        $this->bootDir(ExecutorEnum::SEEDERS);
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->createSeeder(...$this->prepareExecutor(ExecutorEnum::SEEDER));
    }

    /**
     * @param string $seeder
     * @param string $namespace
     * @param string $path
     * @return void
     */
    protected function createSeeder($seeder, $namespace, $path)
    {
        $class = Str::studly($seeder);
        if ($this->checkFullClassExists($namespace, $class)) {
            return;
        }

        $stub = $this->getStubContent($namespace, ExecutorEnum::SEEDER);
        $search = ['{$namespace}', '{$class}', '{$table}'];
        $replace = [$namespace, $class, $this->getTable()];
        $stub = str_replace($search, $replace, $stub);

        $this->makeExecutorFile($seeder, $path, $stub);
    }

    protected function getTable()
    {
        if ($this->option('table')) {
            return $this->option('table');
        }
        return '';
    }
}
