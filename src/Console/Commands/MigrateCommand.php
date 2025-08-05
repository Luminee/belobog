<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Facades\DB;
use Luminee\Belobog\Console\Concerns\ExecuteConcern;
use Luminee\Belobog\Database\Migration;
use Luminee\Belobog\Enums\ExecutorEnum;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

class MigrateCommand extends Command
{
    use Directory, ExecuteConcern;

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

        $this->configs = [
            'action' => 'migrate',
            'table_name' => ExecutorEnum::MIGRATIONS,
            'table_key' => ExecutorEnum::MIGRATION,
            'create_file_name' => 'create_migrations_table.php',
        ];
    }

    protected function bootDir()
    {
        $this->executor_dir = realpath(config('belobog.migrations.dir'));
        $this->executor_namespace = config('belobog.migrations.namespace');
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws
     */
    public function handle()
    {
        $this->handleDirectories($this->prepareDir($this->executor_dir));

        $this->prepareRunAndPrint();

        $this->switcherRun(function ($executor, $item, $conn) {
            if (empty($class = $item['class'] ?? null) || !($class instanceof Migration)) {
                return;
            }
            $record = $item['record'] ?? null;
            $class->init($conn, $this->print ? 0 : ($record->iteration ?? 0));
            $class->up();
            $this->run ?
                $this->migrate($class, $executor, $record) :
                $this->print($class, $executor, $record);
        }, explode(',', $this->option('conn') ?: DB::getDefaultConnection()));
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
        $this->recordExecutor($name, $record, $ite);
        $this->info($name . ' Migrate.');
        $this->count++;
    }
}
