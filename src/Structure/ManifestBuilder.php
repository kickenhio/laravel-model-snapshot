<?php namespace Kickenhio\LaravelSqlSnapshot\Structure;

use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Structure\Mutations\ManifestAttributeMutation;
use Kickenhio\LaravelSqlSnapshot\Structure\Mutations\TableDataMutations;

class ManifestBuilder
{
    protected string $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    /**
     * @return DatabaseManifest
     * @throws InvalidManifestSyntaxException
     */
    public function build(): DatabaseManifest
    {
        $config = json_decode(file_get_contents($this->path), true);
        $manifest = new DatabaseManifest();

        if (!isset($config['models'])) {
            throw new InvalidManifestSyntaxException('Config must have "models" config.');
        }

        foreach ($config['models'] as $name => $model) {
            $modelStruct = new EntrypointModel($name, $model['table'], $model['entrypoint'] ?? ['ID' => 'id']);

            $modelStruct->registerBefore($model['before'] ?? []);
            $modelStruct->registerAfter($model['after'] ?? []);

            $manifest->addModel($modelStruct);
        }

        foreach ($config['table_mutations'] as $tableName => $mutation) {
            $manifestMutation = new TableDataMutations($tableName);

            foreach ($mutation['ignore'] ?? [] as $ignore) {
                $manifestMutation->addIgnoreColumn($ignore);
            }

            foreach ($mutation['attributes'] ?? [] as $name => $attribute) {
                $manifestMutation->addAttributeMutation(
                    new ManifestAttributeMutation($name, $attribute['method'], $attribute['value'])
                );
            }

            $manifest->addMutation($manifestMutation);
        }

        return $manifest;
    }
}