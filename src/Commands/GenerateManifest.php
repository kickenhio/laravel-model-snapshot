<?php namespace Kickenhio\LaravelSqlSnapshot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

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
     * @var array<string>
     */
    protected array $models = [];

    /**
     * @return int
     */
    public function handle(): int
    {
        $added = [];
        $this->connection = $this->argument('connection');
        $this->dbName = DB::connection($this->connection)->getDatabaseName();
        $filename = sprintf('%s.json',$this->ask('Filename?', 'example'));

        $rootNode = [
            'models' => [],
            'table_mutations' => []
        ];

        if (file_exists(resource_path($filename))) {
            $rootNode = json_decode(file_get_contents(resource_path($filename)), true);
            foreach ($rootNode['models'] as $name => $struct) {
                $this->models[$name] = $struct['table'];
            }
        }

        while (!empty($table = $this->askWithCompletion('Select table for Model', ['ecommerce_clients']))) {
            do { $modelName = $this->ask('Model name'); } while (empty($modelName));

            $this->models[$modelName] = $table;
            $added[$modelName] = $table;
        }

        foreach ($added as $name => $table) {
            $this->output->success("Processing model $name");

            $rootNode['models'][$name] = [
                'table' => $table,
                'entrypoint' => [
                    'ID' => 'id'
                ],
                'before' => $this->retrieveBefore($table, [$table]),
                'after'  => $this->retrieveAfter($table, [$table]),
            ];

            if (!empty($mutate = $this->applyMutations($table))) {
                $rootNode['table_mutations'][$table] = $mutate;
            }
        }

        file_put_contents(resource_path($filename), json_encode($rootNode, JSON_PRETTY_PRINT));

        return CommandAlias::SUCCESS;
    }

    /**
     * @param string $table
     *
     * @return string|null
     */
    private function selectModelNode(string $table): ?string {
        $matches = [];
        $default = 'None';

        foreach ($this->models as $model => $tableName) {
            if ($tableName == $table) {
                $matches[] = $model;
            }
        }

        if (count($matches) < 1) {
            return null;
        }

        if (count($matches) == 1) {
            $default = $matches[0];
        }

        if ('None' !== $choice = $this->choice("Found model associated for table '$table'. Use it as relation?", array_merge(['None'], $matches), $default)) {
            return $choice;
        }

        return null;
    }

    /**
     * @param string $table
     * @param array $chain
     *
     * @return array
     */
    private function retrieveBefore(string $table, array $chain): array
    {
        $this->output->warning("Begin associate before relations for $table");

        $before = [];
        $relations = DB::connection($this->connection)->select("
            SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE CONSTRAINT_SCHEMA = '{$this->dbName}'
            AND TABLE_NAME = '{$table}'"
        );

        foreach ($relations as $index) {
            if (!is_null($index->REFERENCED_TABLE_SCHEMA)) {
                if ($table == $index->REFERENCED_TABLE_NAME) {
                    continue;
                }

                $plus = array_merge($chain, [$index->REFERENCED_TABLE_NAME]);
                $this->info(implode('->', $plus));

                if ($modelNode = $this->selectModelNode($index->REFERENCED_TABLE_NAME)) {
                    $before[] = [
                        "method"    => "model",
                        "model"     => $modelNode,
                        "input"     => $index->COLUMN_NAME
                    ];

                    continue;
                }

                $node = [
                    "method"    => "related",
                    "table"     => $index->REFERENCED_TABLE_NAME,
                    "input"     => $index->COLUMN_NAME,
                    "reference" => $index->REFERENCED_COLUMN_NAME,
                    'before'    => $this->retrieveBefore($index->REFERENCED_TABLE_NAME, $plus),
                    'after'     => $this->retrieveAfter($index->REFERENCED_TABLE_NAME, $plus),
                ];

                $before[] = array_filter($node);
            }
        }

        return $before;
    }

    /**
     * @param $table
     * @param array $chain
     *
     * @return array
     */
    private function retrieveAfter($table, array $chain): array
    {
        $after = [];
        $relations = DB::connection($this->connection)->select("
            SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE CONSTRAINT_SCHEMA = '{$this->dbName}' 
            AND REFERENCED_TABLE_NAME = '{$table}'
        ");

        foreach ($relations as $index) {
            if (!is_null($index->REFERENCED_TABLE_SCHEMA)) {
                if ($table == $index->TABLE_NAME) {
                    continue;
                }

                if (count($chain) > 1 AND $chain[count($chain)-2] === $index->TABLE_NAME) {
                    continue;
                }

                $plus = array_merge($chain, [$index->TABLE_NAME]);
                $this->info(implode('->', $plus) . '?');

                if (!$this->confirm("Should import all '{$index->TABLE_NAME}' which has '{$index->COLUMN_NAME}' equal {$table}.{$index->REFERENCED_COLUMN_NAME}")) {
                    continue;
                }

                if ($modelNode = $this->selectModelNode($index->TABLE_NAME)) {
                    $after[] = [
                        "method"    => "model",
                        "model"     => $modelNode,
                        "input"     => $index->REFERENCED_COLUMN_NAME
                    ];

                    $this->output->info("Pair table {$index->TABLE_NAME} for {$table}.{$index->REFERENCED_COLUMN_NAME} values");
                    continue;
                }

                $node = [
                    "method"    => "related",
                    "table"     => $index->TABLE_NAME,
                    "input"     => $index->REFERENCED_COLUMN_NAME,
                    "reference" => $index->COLUMN_NAME,
                    'before'    => $this->retrieveBefore($index->TABLE_NAME, $plus),
                    'after'     => $this->retrieveAfter($index->TABLE_NAME, $plus),
                ];

                $this->output->info("Pair table {$index->TABLE_NAME} for {$table}.{$index->REFERENCED_COLUMN_NAME} values");
                $after[] = array_filter($node);
            }
        }

        return $after;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    private function applyMutations(string $table): array
    {
        $mutations = [
            'ignore'     => [],
            'attributes' => []
        ];

        $tableData = DB::connection($this->connection)->select("
            SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '{$this->dbName}' 
            AND TABLE_NAME = '{$table}'
        ");

        $fakers = [
            'city' => 'city',
            'email' => 'email',
            'street' => 'street',
            'lastName' => 'lastName',
            'last_name' => 'lastName',
            'firstName' => 'firstName',
            'first_name' => 'firstName',
        ];

        foreach ($tableData as $columnData) {
            if ($columnData->EXTRA == 'VIRTUAL GENERATED') {
                $mutations['ignore'][] = $columnData->COLUMN_NAME;
            }

            foreach ($fakers as $pattern => $faker) {
                if (isset($fakers[$columnData->COLUMN_NAME])) {
                    $mutations['attributes'][$columnData->COLUMN_NAME] = [
                        "method" => "faker",
                        "value"  => $pattern
                    ];
                }
            }
        }

        return array_filter($mutations);
    }
}