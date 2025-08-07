<?php

namespace Luminee\Belobog\Console\Commands;

use Exception;
use Illuminate\Support\Facades\DB;
use Luminee\Belobog\Console\Concerns\ExecuteConcern;
use Luminee\Belobog\Database\Seeder;
use Luminee\Belobog\Enums\ExecutorEnum;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;
use ReflectionClass;
use ReflectionMethod;

class SeedCommand extends Command
{
    use Directory, ExecuteConcern;

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

        $this->configs = [
            'action' => 'seed',
            'table_name' => ExecutorEnum::SEEDERS,
            'table_key' => ExecutorEnum::SEEDER,
            'create_file_name' => 'create_seeders_table.php',
        ];
    }

    protected function bootDir()
    {
        $this->executor_dir = realpath(config('belobog.seeders.dir'));
        $this->executor_namespace = config('belobog.seeders.namespace');
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws
     */
    public function handle()
    {
        if ($this->option('optimize')) {
            exec("composer -o dump");
        }

        $this->handleDirectories($this->prepareDir($this->executor_dir));

        $print = $this->prepareRunAndPrint();

        $this->switcherRun(function ($executor, $item, $conn) use ($print) {
            if (empty($class = $item['class'] ?? null) || !($class instanceof Seeder)) {
                return;
            }
            $record = $item['record'] ?? null;
            $class->init($conn, $print ? 0 : ($record->iteration ?? 0), $this->run);
            $this->run ?
                $this->seed($class, $executor, $record) :
                $this->print($class, $executor, $record, $item['file']);
        }, explode(',', $this->option('conn') ?: DB::getDefaultConnection()));
    }

    protected function seed(Seeder $class, $executor, $record)
    {
        $class->run();
        $ite = $class->getLocalIteration();
        if ($class->isUseIteration() && ($record->iteration ?? 0) >= $ite) {
            $this->line("[$executor] Has been seed...");
            return;
        }
        $this->recordExecutor($executor, $record, $ite);
        $this->info($executor . ' Seed.');
        $this->count++;
    }

    protected function print(Seeder $class, $executor, $record, $file)
    {
        if ($class->isUseIteration()) {
            $class->run();
        }
        if (($record->iteration ?? 0) >= $class->getLocalIteration() && !$this->option('print')) {
            return;
        }
        $this->comment($executor . ' Seeder: ');
        try {
            // 读取文件内容
            $fileContent = file($file);

            // 通过反射获取表名
            $reflection = new ReflectionClass($class);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $table = $tableProperty->getValue($class);

            if ($class->isUseIteration()) {
                $this->info('Record Iteration : ' . ($record->iteration ?? 0));
                $this->info('Local Iteration : ' . $class->getLocalIteration() . "\r\n");
            }
            if ($table) {
                $this->info('Table : ' . $table . "\r\n");
            }

            // 使用反射获取 run 方法
            $reflection = new ReflectionMethod($class, 'run');

            // 获取方法所在的文件和起始/结束行
            $startLine = $reflection->getStartLine() - 1;
            $endLine = $reflection->getEndLine();

            // 提取方法体内容（去掉方法声明）
            $methodBody = array_slice($fileContent, $startLine, $endLine - $startLine);
            $content = '';
            foreach ($methodBody as $k => $line) {
                $content .= in_array($k, [0, 1, count($methodBody) - 1]) ? "<fg=blue>" . $line . "</fg=blue>" : $line;
            }

            // 输出方法内容
            $this->line($content);
        } catch (Exception $e) {
            $this->error("无法获取方法内容: " . $e->getMessage());
        }
    }
}
