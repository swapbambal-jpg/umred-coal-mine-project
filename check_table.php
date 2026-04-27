<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

try {
    // Check the actual table structure
    $schema = DB::select('DESCRIBE delivery_orders');
    
    foreach($schema as $column) {
        if($column->Field == 'status') {
            echo "Status column type: " . $column->Type . "\n";
            echo "Status column null: " . $column->Null . "\n";
            echo "Status column default: " . $column->Default . "\n";
        }
    }
    
    // Also check current values in the table
    $statuses = DB::select('SELECT DISTINCT status FROM delivery_orders');
    echo "\nCurrent status values in table:\n";
    foreach($statuses as $status) {
        echo "- " . $status->status . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
