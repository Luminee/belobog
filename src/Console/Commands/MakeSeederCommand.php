<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

/**
 * @author LuminEe
 */
class MakeSeederCommand extends Command
{
    use Directory;

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
     * @var string
     */
    protected $stub_dir;

    /**
     * @var string
     */
    protected $seeder_dir;

    /**
     * @var string
     */
    protected $seeder_namespace;

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
        $this->seeder_dir = realpath(config('belobog.seeders.dir'));
        $this->seeder_namespace = config('belobog.seeders.namespace');
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws
     */
    public function handle()
    {
        $dir = $this->seeder_dir;
        $namespace = $this->seeder_namespace;
        if ($seeder = $this->argument('seeder')) {
            $stulied = $this->stulyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $stulied);
            $this->makeDirectory($dir);

            if ($namespace) {
                $namespace .= '\\' . implode('\\', $stulied);
            }
        } else {
            $seeder = $this->argument('directory');
        }
        $this->createSeeder($seeder, $namespace, $dir);
    }

    /**
     * @param $seeder
     * @param $namespace
     * @param $path
     */
    protected function createSeeder($seeder, $namespace, $path)
    {
        $class = Str::studly($seeder);
        if ($namespace) {
            $full_class = $namespace . '\\' . $class;
            if (class_exists($full_class)) {
                $this->error("Class $full_class Has Exist!");
                return;
            }
        }

        $seeder_dir = $namespace && !$this->option('anonymous') ? '' : '/anonymous';
        $stub_file = $this->stub_dir . $seeder_dir . "/seeder.stub";
        $stub = $this->files->get($stub_file);
        $search = ['{$namespace}', '{$class}', '{$table}'];
        $replace = [$namespace, $class, $this->getTable()];
        $stub = str_replace($search, $replace, $stub);

        $name = date('Y_m_d_His') . '_' . $seeder;
        $file = $path . '/' . $name . '.php';
        $this->files->put($file, $stub);
        if ($this->option('optimize')) {
            exec("composer -o dump");
        }

        $this->info("File $name.php Create Success! [in $path]");
    }

    protected function getTable()
    {
        if ($this->option('table')) {
            return $this->option('table');
        }
        return '';
    }
}
