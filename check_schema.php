<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schema = Illuminate\Support\Facades\Schema::getConnection()->getDoctrineSchemaManager();
$table = $schema->listTableDetails('stf_register');

echo "Table: stf_register\n";
foreach($table->getColumns() as $col) {
    echo sprintf("%-20s %s%s\n", $col->getName(), $col->getType()->getName(), $col->getUnsigned() ? ' unsigned' : '');
}
