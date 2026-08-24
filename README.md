# Belobog

Laravel 迁移与种子管理工具 —— 基于 Schema Builder 的链式 API，支持迭代追踪与多数据库切换。

## 安装

```bash
composer require luminee/belobog
```

## 快速开始

### 迁移

```php
use Luminee\Belobog\Database\Migration;

class CreateUsersTable extends Migration
{
    protected $table = 'users';

    public function up()
    {
        $this->create()->engine();
        $this->increments('id');
        $this->string('name')->nullable();
        $this->t_id('account');          // integer('account_id')
        $this->timestamps(true, false);  // created_at, updated_at
        $this->idx_id('account');        // index('account_id')
    }
}
```

```bash
php artisan luminee:migrate trade.order --conn=dev --run
```

### 种子

```php
use Luminee\Belobog\Database\Seeder;

class InitUsersSeeder extends Seeder
{
    protected $table = 'users';
    protected $useIteration = true;

    public function run()
    {
        $this->create(['name' => 'admin', 'email' => 'admin@test.com']);
    }
}
```

```bash
php artisan luminee:seed trade.order --conn=dev --run
```

### 生成

```bash
php artisan luminee:make:migration trade.order create_users_table
php artisan luminee:make:seeder trade.order init_users
php artisan luminee:make:script trade.order sync_data
```

## 命令参考

| 命令 | 说明 |
|---|---|
| `luminee:migrate {directory?}` | 执行迁移，支持 `--conn` `--class` `--run` `--print` `--pretty` |
| `luminee:seed {directory?}` | 执行种子，选项同上 |
| `luminee:make:migration {directory} {migration?}` | 生成迁移文件 |
| `luminee:make:seeder {directory} {seeder?}` | 生成种子文件 |
| `luminee:make:script {directory} {script?}` | 生成脚本文件 |

## 迭代追踪

每次 `table()` 调用计为一次迭代，`build()` 后将迭代号写入追踪表。下次执行时自动跳过已完成迭代，同一迁移文件可多次迭代。

## 兼容旧脚本

继承 `Migration` 并使用 `run()` 方法（而非 `up()`）的旧脚本完全兼容，`up()` 默认桥接到 `run()`。

## 辅助方法

| 方法 | 说明 |
|---|---|
| `t_id($name)` | `integer($name . '_id')` |
| `idx_id($name)` | `index($name . '_id')` |
| `kv_pair()` | 生成 key/value 列对 |
| `morphs($name)` | 多态关联列 |
| `timestamps($useCurrent, $nullable, $onUpdate)` | 时间戳列 |
| `maskFilter($name)` | 生成 mask/filter 列对 |
| `dropTable()` | 删除表 |

## 依赖

| 包 | 说明 |
|---|---|
| `luminee/foundry` | 包基础设施 |
| `luminee/chariot` | 命令基类 |
| `luminee/switcher` | 多数据库切换 |

## License

MIT