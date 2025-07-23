<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Facades\DB;
use Luminee\Belobog\Console\Concerns\SeederConcern;
use Luminee\Belobog\Database\Seeder;
use ReflectionException;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;
use Luminee\Migrations\Base\SeederBaseModel;

class SeedCommand extends Command
{
    use Directory, SeederConcern;

    /**
     * @var Switcher
     */
    protected $switcher;

    protected $batch;

    /**
     * @var array
     */
    protected $seeders = [];

    protected $database;

    protected $count;

    protected $run = false;

    protected $dir;

    /**
     * @var string
     */
    protected $seeder_dir;

    /**
     * @var string
     */
    protected $seeder_namespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:seed 
                            {directory? : Directory like project.module}
                            {--conn= : Connection for seed, <see switcher>}
                            {--class= : Which class to run, if is use the namespace}
                            {--table= : Which table to run}
                            {--except= : Except class, if is use the namespace}
                            {--except-table= : Except table} 
                            {--deep : Seed with sub directory seeders}
                            {--common=}
                            {--o|optimize}
                            {--force : Force to seed even it has been seeded}
                            {--run}
                            {--pretty}
                            {--print}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed with project.module and connection';

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
        if ($this->argument('directory')) {
            $stulied = $this->stulyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $stulied);
        }

        if ($this->option('optimize')) {
            exec("composer -o dump");
        }

        $this->seedDirectories($dir);

        $this->run = $this->option('run');
        if ($print = $this->option('print') || $this->option('pretty')) {
            $this->run = false;
        }

        foreach (explode(',', $this->option('conn') ?: DB::getDefaultConnection()) as $conn) {
            $this->count = 0;
            $this->comment('======== Connection on [' . $conn . '] ========');
            $this->switcher->run(function () use ($conn, $print) {
                $this->prepareSeederTable();
                foreach ($this->seeders as $seeder => $item) {
                    if (empty($class = $item['class'] ?? null)) {
                        continue;
                    }
                    if (!($class instanceof Seeder)) {
                        continue;
                    }
                    $record = $item['record'] ?? null;
                    $class->init($conn, $print ? 0 : ($record->iteration ?? 0));
                    $this->run ?
                        $this->seed($class, $seeder, $record) :
                        $this->print($class, $seeder, $record);
                }
            }, $conn);
            if ($this->run) {
                $this->count == 0 ? $this->line("Nothing to seed.") : $this->info("Seed done!");
            }
        }
    }

    /**
     * Seed directories.
     *
     * @param $dir
     */
    protected function seedDirectories($dir)
    {
        foreach (scandir($dir) as $seeder) {
            if (in_array($seeder, ['.', '..', '.gitkeep', '.gitignore'])) {
                continue;
            }

            if (is_dir($dir . '/' . $seeder)) {
                if ($this->option('deep')) {
                    $this->seedDirectories($dir . '/' . $seeder);
                }
            } else {
                $this->seedClass($dir . '/' . $seeder);
            }
        }
    }

    /**
     * Get class for migrate.
     * 
     * @param $file
     */
    protected function seedClass($file)
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
        $seeder_file = basename($file, '.php');
        $this->seeders[$seeder_file]['class'] = $class;
        return;
    }

    protected function printSeeder($content)
    {
        list($_, $content) = explode("extends SeederBaseModel\n", $content);
        $table = $this->getPregStr($content, '/\$table = \'(\S+)\'/');
        $this->info('Table : ' . $table . "\r\n");
        list($_, $content) = explode("public function run()\n", $content);
        $function = ltrim(preg_replace("/}\n}$/", '', trim($content)), "{\n");
        $this->line($function);
    }
}
