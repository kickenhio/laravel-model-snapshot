<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kickenhio\LaravelSqlSnapshot\Structure\DatabaseManifest;
use Kickenhio\LaravelSqlSnapshot\Structure\Relations\Model;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;

class SnapshotQueryDumpBuilder
{
    protected string $connection;
    protected DatabaseManifest $manifest;

    /**
     * Creates new SnapshotQueryBuilder instance
     *
     * @param DatabaseManifest $manifest
     * @param string $connection
     */
    public function __construct(DatabaseManifest $manifest, string $connection)
    {
        $this->manifest = $manifest;
        $this->connection = $connection;
    }

    /**
     * @return DatabaseManifest
     */
    public function getManifest() : DatabaseManifest
    {
        return $this->manifest;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection() : ConnectionInterface
    {
        return DB::connection($this->connection);
    }

    /**
     * @param string $model
     * @param string $entryPointTarget
     * @param mixed $value
     *
     * @return SnapshotResult
     * @throws InvalidManifestSyntaxException
     */
    public function retrieveEntrypoint(string $model, string $entryPointTarget, mixed $value): SnapshotResult
    {
        if ($entryPointTarget === 'id') {
            return $this->retrieve($model, $value);
        }

        $manifestModel = $this->manifest->getEntrypointModel($model);

        if (is_array($entryPointTarget)) {
            $entrypointColumn = $entryPointTarget['column'];
        } else {
            $entrypointColumn = $entryPointTarget;
        }

        $tableName = $manifestModel->getTableName();

        $rows = $this->getConnection()->table($tableName)
            ->where($entrypointColumn, '=', $value)
            ->get()
            ->map(function ($row) use ($model, $tableName) {
                return new ModelRetriever($this, $this->getModel($model), new Row($tableName, (array) $row));
            });

        return new SnapshotResult($rows, $this->formatException($rows));
    }

    /**
     * @param string $model
     * @param int $id
     *
     * @return SnapshotResult
     * @throws InvalidManifestSyntaxException
     */
    public function retrieve(string $model, int $id): SnapshotResult
    {
        $tableName = $this->manifest->getEntrypointModel($model)->getTableName();

        $rows = $this->getConnection()->table($tableName)
            ->where('id', '=', $id)
            ->get()
            ->map(function ($row) use ($model, $tableName) {
                return new ModelRetriever($this, $this->getModel($model), new Row($tableName, (array) $row));
            });

        return new SnapshotResult($rows);
    }

    /**
     * @param string $model
     *
     * @return Model
     * @throws InvalidManifestSyntaxException
     */
    protected function getModel(string $model): Model
    {
        return new Model($this->manifest->getEntrypointModel($model)->getName());
    }

    /**
     * @param Collection $rows
     *
     * @return array
     */
    protected function formatException(Collection $rows): array
    {
        return [];

        //if ($rows->count() < 2) {
        //    return [];
        //}
        //
        //return $rows->map(function($row) use ($entryPointTarget, $entrypointColumn, $model) {
        //    if (!is_array($entryPointTarget) || !isset($entryPointTarget['dupeInfo'])) {
        //        return $row->{$model->getReference()};
        //    }
        //
        //    $data = collect($entryPointTarget['dupeInfo'])->map(function ($key) use ($row) {
        //        return $key . ':' . $row->{$key};
        //    });
        //
        //    return $row->{$model->getReference()} . ' (' . $data->implode(',') . ')';
        //})->toArray();
    }
}