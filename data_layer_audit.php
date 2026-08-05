<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

echo "============================================================\n";
echo "DAR-LTCMS DATA LAYER AUDIT\n";
echo "============================================================\n";
echo "Database: " . $db->getDatabaseName() . PHP_EOL;
echo "Driver: " . $db->getDriverName() . PHP_EOL;

$tables = $db->select("
    SELECT tablename
    FROM pg_catalog.pg_tables
    WHERE schemaname = 'public'
    ORDER BY tablename
");

echo "Table count: " . count($tables) . PHP_EOL;

foreach ($tables as $tableRow) {
    $table = $tableRow->tablename;

    echo PHP_EOL;
    echo "------------------------------------------------------------\n";
    echo strtoupper($table) . PHP_EOL;
    echo "------------------------------------------------------------\n";

    $columns = $db->select("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = ?
        ORDER BY ordinal_position
    ", [$table]);

    echo "Columns:\n";

    foreach ($columns as $column) {
        $nullable = $column->is_nullable === 'YES'
            ? 'nullable'
            : 'required';

        echo "- {$column->column_name} | {$column->data_type} | {$nullable}\n";
    }

    $foreignKeys = $db->select("
        SELECT
            kcu.column_name,
            ccu.table_name AS foreign_table,
            ccu.column_name AS foreign_column
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
          ON tc.constraint_name = kcu.constraint_name
         AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage ccu
          ON ccu.constraint_name = tc.constraint_name
         AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
          AND tc.table_schema = 'public'
          AND tc.table_name = ?
        ORDER BY kcu.column_name
    ", [$table]);

    if ($foreignKeys) {
        echo "Foreign keys:\n";

        foreach ($foreignKeys as $foreignKey) {
            echo "- {$foreignKey->column_name} -> "
                . "{$foreignKey->foreign_table}.{$foreignKey->foreign_column}\n";
        }
    }
}

echo PHP_EOL;
echo "============================================================\n";
echo "APPLICATION MODELS\n";
echo "============================================================\n";

foreach (glob(__DIR__ . '/app/Models/*.php') ?: [] as $modelFile) {
    echo basename($modelFile) . PHP_EOL;
}
