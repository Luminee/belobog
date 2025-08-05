<?php

use Luminee\Belobog\Database\Migration;
use Luminee\Belobog\Enums\ExecutorEnum;

return new class extends Migration {

    /**
     * @var string
     */
    protected $table = ExecutorEnum::SEEDERS;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->create()->engine();
        $this->increments('id');
        $this->string(ExecutorEnum::SEEDER);
        $this->tinyInteger('iteration');
        $this->integer('batch')->default(1);
        $this->text('record');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // drop table
    }
};
