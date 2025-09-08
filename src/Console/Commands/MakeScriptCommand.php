<?php

namespace Luminee\Belobog\Console\Commands;

use Illuminate\Support\Str;
use Luminee\Belobog\Console\Concerns\MakeConcern;
use Luminee\Belobog\Enums\ExecutorEnum;
use Luminee\Chariot\Console\Command;
use Luminee\Foundry\Concerns\Directory;

class MakeScriptCommand extends Command
{
    use Directory, MakeConcern;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:make:script 
                            {directory : The directory of the script, like project.module. If script is not set, directory will be used as script name} 
                            {script? : The name of the script, like init_user. If script is not set, directory will be used as script name} 
                            {--a|anonymous : Make anonymous script}
                            {--o|optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make script to project.module direction';

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

        $this->bootDir(ExecutorEnum::SCRIPTS);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createScript(...$this->prepareExecutor(ExecutorEnum::SCRIPT));
    }

    protected function createScript($signature, $namespace, $path)
    {
        $script = str_replace([':', '-'], '_', $signature);
        $class = Str::studly($script);
        if ($this->checkFullClassExists($namespace, $class)) {
            return;
        }

        $stub = $this->getStubContent($namespace, ExecutorEnum::SCRIPT);
        $search = ['{$namespace}', '{$class}', '{$signature}'];
        $replace = [$namespace, $class, $signature];
        $stub = str_replace($search, $replace, $stub);

        $this->makeExecutorFile($script, $path, $stub);
    }
}
