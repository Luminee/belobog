<?php

namespace Luminee\Belobog\Console\Concerns;

trait MakeConcern
{
    /**
     * @var string
     */
    protected $stub_dir;

    /**
     * @var string
     */
    protected $executor_dir;

    /**
     * @var string
     */
    protected $executor_namespace;

    protected function bootDir($executor)
    {
        $this->executor_dir = realpath($dir = config("belobog.$executor.dir"));
        if (!$this->executor_dir) {
            mkdir($dir, 0755, true);
            $this->executor_dir = realpath($dir);
        }
        $this->executor_namespace = config("belobog.$executor.namespace");
    }

    /**
     * @param string $executor_arg_name
     * @return array
     */
    protected function prepareExecutor($executor_arg_name)
    {
        $dir = $this->executor_dir;
        $namespace = $this->executor_namespace;
        if ($executor = $this->argument($executor_arg_name)) {
            $stulied = $this->stulyDirectory($this->argument('directory'));
            $dir .=  '/' . implode('/', $stulied);
            $this->makeDirectory($dir);

            if ($namespace) {
                $namespace .= '\\' . implode('\\', $stulied);
            }
        } else {
            $executor = $this->argument('directory');
        }

        return [$executor, $namespace, $dir];
    }

    protected function checkFullClassExists($namespace, $class)
    {
        if ($namespace) {
            $full_class = $namespace . '\\' . $class;
            if (class_exists($full_class)) {
                $this->error("Class $full_class Has Exist!");
                return true;
            }
        }
        return false;
    }

    protected function getStubContent($namespace, $stub_name)
    {
        $anonymous_dir = $namespace && !$this->option('anonymous') ? '' : '/anonymous';
        $stub_file = $this->stub_dir . $anonymous_dir . "/$stub_name.stub";
        return $this->files->get($stub_file);
    }

    protected function makeExecutorFile($executor, $path, $stub)
    {
        $name = date('Y_m_d_His') . '_' . $executor;
        $file = $path . '/' . $name . '.php';
        $this->files->put($file, $stub);
        if ($this->option('optimize')) {
            exec("composer -o dump");
        }

        $this->info("File $name.php Create Success! [in $path]");
    }
}
