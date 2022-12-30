<?php namespace Kickenhio\LaravelSqlSnapshot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Kickenhio\LaravelSqlSnapshot\Facades\Snapshot as SnapshotSQL;

class GenerateManifest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-snapshot:generate {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves SQL queries to replicate DB entries';

    /**
     * @var string
     */
    protected string $dbName;

    /**
     * @var string
     */
    protected string $connection;

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->connection = $this->argument('connection');
        $this->dbName = DB::connection($this->connection)->getDatabaseName();
        $models = [];
        $pairNames = [];

        while (!empty($table = $this->askWithCompletion('Select table for Model', ['ecommerce_clients']))) {
            do { $modelName = $this->ask('Model name'); } while (empty($modelName));
            $pairNames[$modelName] = $table;
        }

        foreach ($pairNames as $name => $table) {
            $models[$name] = [
                'table' => $table,
                'entrypoint' => [
                    'ID' => 'id'
                ],
                'before' => $this->retrieveBefore($table),
                'after'  => $this->retrieveAfter($table),
            ];
        }

        $rootNode = [
            'models' => $models,
            'table_mutations' => []
        ];

        file_put_contents(resource_path($this->ask('Filename?', 'example').'output.json'), json_encode($rootNode, JSON_PRETTY_PRINT));

        return CommandAlias::SUCCESS;
    }

    /**
     * @param $table
     *
     * @return array
     */
    private function retrieveBefore($table): array
    {
        $before = [];
        $relations = DB::connection($this->connection)->select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = '{$this->dbName}' AND TABLE_NAME = '{$table}'");

        foreach ($relations as $index) {
            if (!is_null($index->REFERENCED_TABLE_SCHEMA)) {
                if ($table == $index->REFERENCED_TABLE_NAME) {
                    continue;
                }

                $node = [
                    "method"    => "related",
                    "table"     => $index->REFERENCED_TABLE_NAME,
                    "input"     => $index->COLUMN_NAME,
                    "reference" => $index->REFERENCED_COLUMN_NAME,
                    'before'    => $this->retrieveBefore($index->REFERENCED_TABLE_NAME),
                    //'after'     => $this->retrieveAfter($index->REFERENCED_TABLE_NAME),
                ];

                $before[] = array_filter($node);
            }
        }

        return $before;
    }

    /**
     * @param $table
     *
     * @return array
     */
    private function retrieveAfter($table): array
    {
        $after = [];
        $relations = DB::connection($this->connection)->select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = '{$this->dbName}' AND REFERENCED_TABLE_NAME = '{$table}'");

        foreach ($relations as $index) {
            if (!is_null($index->REFERENCED_TABLE_SCHEMA)) {
                if ($table == $index->TABLE_NAME) {
                    continue;
                }

                if (!$this->confirm("Should {$table} load data from {$index->TABLE_NAME}.{$index->COLUMN_NAME}")) {
                    continue;
                }

                $node = [
                    "method"    => "related",
                    "table"     => $index->TABLE_NAME,
                    "input"     => $index->REFERENCED_COLUMN_NAME,
                    "reference" => $index->COLUMN_NAME,
                    'before'    => $this->retrieveBefore($index->TABLE_NAME),
                    //'after'     => $this->retrieveAfter($index->TABLE_NAME),
                ];

                $after[] = array_filter($node);
            }
        }

        return $after;
    }
}