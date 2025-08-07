<?php

namespace Luminee\Belobog\Contracts;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;

abstract class Migration extends Belobog
{
    /**
     * The schema grammar instance.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * @var Blueprint
     */
    protected $blueprint;

    /**
     * @var Fluent
     */
    protected $column;

    /**
     * @var array
     */
    protected $statements = [];

    /**
     * @var bool
     */
    protected $need_to_sql = false;

    /**
     * @var bool
     */
    protected $updateAddition = false;

    protected $strIndex = false;

    protected $modify = [];

    protected $removeModifyPrimaryKey = false;

    protected $blueprintFuncList = [
        'bigIncrements',
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'dateTimeTz',
        'decimal',
        'double',
        'enum',
        'float',
        'geometry',
        'geometryCollection',
        'increments',
        'integer',
        'ipAddress',
        'json',
        'jsonb',
        'lineString',
        'longText',
        'macAddress',
        'mediumIncrements',
        'mediumInteger',
        'mediumText',
        'morphs',
        'multiLineString',
        'multiPoint',
        'multiPolygon',
        'nullableMorphs',
        'nullableTimestamps',
        'point',
        'polygon',
        'rememberToken',
        'renameColumn',
        'smallIncrements',
        'smallInteger',
        'softDeletes',
        'softDeletesTz',
        'string',
        'text',
        'time',
        'timeTz',
        'timestamp',
        'timestampTz',
        'timestamps',
        'tinyIncrements',
        'tinyInteger',
        'unsignedBigInteger',
        'unsignedDecimal',
        'unsignedInteger',
        'unsignedMediumInteger',
        'unsignedSmallInteger',
        'unsignedTinyInteger',
        'uuid',
        'year',
        'index',
        'unique',
        'primary',
        'dropUnique',
        'dropIndex',
        'dropColumn'
    ];

    protected $fluentFuncList = [
        'after',
        'autoIncrement',
        'charset',
        'collation',
        'default',
        'first',
        'nullable',
        'storedAs',
        'unsigned',
        'useCurrent',
        'change'
    ];

    protected $fluentIgnoreFuncList = [
        'comment'
    ];

    /**
     * MigrationBaseModel constructor.
     */
    public function __construct()
    {
        $this->grammar = new MySqlGrammar();
    }

    public function build()
    {
        foreach ($this->getStatements() as $statement) {
            $this->connection->statement($statement);
        }
        return $this->localIteration;
    }

    public function prepare()
    {
        $output = '';
        $pretty = [];
        foreach ($this->getStatements() as $statement) {
            $pretty[] = $this->pretty($statement);
            $output .= '::=> ' . $statement . "\r\n";
        }
        return [$output, $pretty, $this->localIteration];
    }

    protected function pretty($statement)
    {
        if (strpos($statement, 'create table') !== 0) {
            return [$statement];
        }
        preg_match('/\([^()]*(?:\([^()]*\)[^()]*)*\)/', $statement, $matches);
        $column_str = substr($matches[0], 1, -1);
        list($front, $end) = explode($column_str, $statement);
        $pretty = [$front];
        $columns = explode(',', $column_str);
        foreach ($columns as $k => $column) {
            $pretty[] = "    " . trim($column) . ($k == count($columns) - 1 ? '' : ',');
        }
        $pretty[] = $end;
        return $pretty;
    }

    public function getSql()
    {
        return $this->getStatements();
    }

    protected function toSql()
    {
        foreach ($this->blueprint->toSql($this->connection, $this->grammar) as $statement) {
            if ($this->strIndex && strpos($statement, 'alter') === 0)
                $statement = preg_replace('/`\(`(\w+)\((\d+)\)`\)/', '`(`$1`($2))', $statement);
            if ($this->updateAddition && strpos($statement, 'alter') === 0)
                $statement .= ', ALGORITHM=INPLACE, LOCK=NONE';
            if (!empty($this->modify) && strpos($statement, 'alter') === 0)
                $statement = $this->modifyStatement($statement);

            $this->statements[] = $statement;
        }
        if ($this->localIteration <= $this->iteration) $this->statements = [];
        $this->need_to_sql = false;
    }

    protected function getStatements()
    {
        if ($this->need_to_sql)
            $this->toSql();
        return $this->statements;
    }

    protected function table($table = null)
    {
        if (is_null($table))
            $table = $this->table;
        if ($this->need_to_sql)
            $this->toSql();
        $this->need_to_sql = true;
        $this->localIteration++;

        $this->modify = [];
        $this->removeModifyPrimaryKey = false;
        $this->blueprint = new Blueprint($table);
        return $this;
    }

    protected function create($table = null)
    {
        $this->table($table);
        $this->blueprint->create();
        return $this;
    }

    protected function engine($engine = 'InnoDB')
    {
        $this->blueprint->engine = $engine;
        return $this;
    }

    protected function updateAddition()
    {
        $this->updateAddition = true;
        return $this;
    }

    protected function strIndex()
    {
        $this->strIndex = true;
        return $this;
    }

    protected function modify($columns)
    {
        is_array($columns) ?
            $this->modify = array_merge($this->modify, $columns) :
            $this->modify[] = $columns;
        return $this;
    }

    protected function removeModifyPrimaryKey()
    {
        $this->removeModifyPrimaryKey = true;
        return $this;
    }

    private function modifyStatement($statement)
    {
        if ($this->removeModifyPrimaryKey)
            $statement = str_replace(' primary key', '', $statement);
        $items = explode('add', $statement);
        foreach ($items as $key => $item) {
            if ($key == 0) continue;
            preg_match('/^ `(\S+)` /', $item, $match);
            $imp = !empty($match[1]) && in_array($match[1], $this->modify) ? 'modify' : 'add';
            $items[$key] = $imp . $item;
        }
        return implode('', $items);
    }

    /**
     * @param $method
     * @param $args
     * @return $this | Blueprint
     */
    public function __call($method, $args)
    {
        if (in_array($method, $this->blueprintFuncList)) {
            $this->column = $this->blueprint->$method(...$args);
        }

        if (in_array($method, $this->fluentFuncList)) {
            $this->column->$method(...$args);
        }

        if (in_array($method, $this->fluentIgnoreFuncList) && $this->conn == 'dev') {
            $this->column->$method(...$args);
        }

        return $this;
    }
}
