<?php namespace Kickenhio\LaravelSqlSnapshot\Structure;

use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Structure\Mutations\TableDataMutations;

class DatabaseManifest
{
    protected array $models = [];
    protected array $mutations = [];
    protected array $collections = [];

    /**
     * @param EntrypointModel $model
     *
     * @return void
     * @throws InvalidManifestSyntaxException
     */
    public function addModel(EntrypointModel $model): void
    {
        if (isset($this->models[$model->getName()])) {
            throw new InvalidManifestSyntaxException('Model already exists');
        }

        $this->models[$model->getName()] = $model;
    }

    /**
     * @return array
     */
    public function entrypointModels() : array
    {
        return $this->models;
    }

    /**
     * @param string $modelName
     *
     * @return EntrypointModel
     * @throws InvalidManifestSyntaxException
     */
    public function getEntrypointModel(string $modelName) : EntrypointModel
    {
        if (!isset($this->models[$modelName])) {
            throw new InvalidManifestSyntaxException("No definition for model $modelName in manifest file");
        }

        return $this->models[$modelName];
    }

    /**
     * @param TableDataMutations $tableMutation
     * @return void
     */
    public function addMutation(TableDataMutations $tableMutation): void
    {
        $this->mutations[$tableMutation->getTableName()] = $tableMutation;
    }

    /**
     * @param string $tableName
     * @return null|TableDataMutations
     */
    public function getTableMutation(string $tableName): ?TableDataMutations
    {
        return $this->mutations[$tableName] ?? null;
    }
}