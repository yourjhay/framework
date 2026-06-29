<?php

namespace Simple\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;

abstract class Migration
{
    abstract public function up(): void;

    abstract public function down(): void;

    protected function schema(): \Illuminate\Database\Schema\Builder
    {
        return Capsule::connection()->getSchemaBuilder();
    }
}
