<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

use Illuminate\Support\Collection;
use Kickenhio\LaravelSqlSnapshot\Contract\Relation;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Structure\Relations\BeforeAfterCallbacks;
use Kickenhio\LaravelSqlSnapshot\Structure\Relations\Dropper;
use Kickenhio\LaravelSqlSnapshot\Structure\Relations\Model;
use Kickenhio\LaravelSqlSnapshot\Structure\Relations\Table;

class ModelRetriever
{
    protected SnapshotQueryDumpBuilder $builder;
    protected Supervisor $supervisor;
    protected Model $model;
    protected Row $row;

    /**
     * Creates new ModelRetriever instance
     *
     * @param SnapshotQueryDumpBuilder $builder
     * @param Model $model
     * @param Row $row
     */
    public function __construct(SnapshotQueryDumpBuilder $builder, Model $model, Row $row)
    {
        $this->supervisor = new Supervisor();
        $this->builder = $builder;
        $this->model = $model;
        $this->row = $row;
    }

    /**
     * Retrieves row used to load for model
     *
     * @return Row
     */
    public function getRow(): Row
    {
        return $this->row;
    }

    /**
     * Changes supervisor instance to prevent duplication queries
     *
     * @param Supervisor $supervisor
     *
     * @return ModelRetriever
     */
    public function withSupervisor(Supervisor $supervisor): ModelRetriever
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    /**
     * Processing SQL scrapping for Model
     *
     * @param bool $wrapTransaction
     *
     * @return Queries
     * @throws \Exception
     */
    public function toSql(bool $wrapTransaction = true): Queries
    {
        return $this->ImportModel($this->model, $this->row->id(), $this->row->parent());
    }

    /**
     * Processing relation type Model
     *
     * @param Model $model
     * @param int $modelID
     * @param object|null $parent
     *
     * @return Queries
     * @throws \Exception
     */
    protected function ImportModel(Model $model, int $modelID, Row $parent = null): Queries
    {
        if (is_null($manifestModel = $this->builder->getManifest()->getEntrypointModel($model->getName()))) {
            throw new InvalidManifestSyntaxException("No configuration for model {$model->getName()}");
        }

        $records = $this->builder->getConnection()
            ->table($manifestModel->getTableName())
            ->where($model->getReference(), '=', $modelID)
            ->get()
            ->map(function ($row) use ($parent, $manifestModel) {
                return new Row($manifestModel->getTableName(), (array) $row, $parent);
            });

        $queries = new Queries();
        $queries->append("-- SELECT * FROM {$manifestModel->getTableName()} WHERE {$model->getReference()} = {$modelID};");

        if (empty($model->getAsk())) {
            return $queries->merge($this->prepareQueryBlock($records, $manifestModel));
        }

        $uid = bin2hex(random_bytes(6));

        $queries->append("-- SNAP:BEGIN [$uid]");
        $queries->append("-- SNAP:MESSAGE [{$model->getAsk()}]");
        $queries->append("-- SNAP:WHEN_EMPTY [SELECT * FROM {$manifestModel->getTableName()} WHERE {$model->getReference()} = {$modelID}]");
        $queries->merge($this->prepareQueryBlock($records, $manifestModel));
        $queries->append("-- SNAP:END [$uid]");

        return $queries;
    }

    /**
     * Loading queries for rows collection
     *
     * @param Collection $result
     * @param BeforeAfterCallbacks $relation
     *
     * @return Queries
     * @throws \Exception
     */
    protected function prepareQueryBlock(Collection $result, BeforeAfterCallbacks $relation): Queries
    {
        return $result->reduce(function (Queries $queries, Row $row) use ($relation) {
            if ($this->supervisor->mark($row->fingerprint())) {
                return $queries;
            }

            return $queries
                ->merge($this->retrieveRelations($relation->getBefore(), $row))
                ->append($this->getQueryFromRecord($row))
                ->merge($this->retrieveRelations($relation->getAfter(), $row));
        }, new Queries());
    }

    /**
     * @param string $pattern
     * @param object $parent
     * @return object|null
     */
    private function walk(string $pattern, object $parent) {
        $value = explode('.', $pattern);

        if (isset($value[0]) && $value[0] == $parent->__tableName) {
            $forward = array_slice($value, 1);

            if (empty($forward)) {
                return $parent;
            }

            return $this->walk(implode('.', $forward), unserialize($parent->__parent));
        }

        return null;
    }

    /**
     * @param Table $relation
     * @param string $value
     * @param Row|null $parent
     *
     * @return Queries
     * @throws \Exception
     */
    protected function ImportRelation(Table $relation, string $value, Row $parent = null): Queries {
        $records = $this->builder->getConnection()
            ->table($relation->getTableName())
            ->where($relation->getReference(), '=', $value)
            ->get()
            ->map(function ($row) use ($parent, $relation) {
                return new Row($relation->getTableName(), (array) $row, $parent);
            });

        $queries = new Queries();
        $queries->append("-- SELECT * FROM {$relation->getTableName()} WHERE {$relation->getReference()} = {$value};");

        //if (!empty($relation->getFilters())) {
        //    foreach ($relation->getFilters() as $nesting => $rule) {
        //        if (!is_null($match = $this->walk($nesting, $parent))) {
        //            $records = $records->reject(function ($record) use ($rule, $match) {
        //                $passes = true;
        //
        //                foreach ($rule as $recordValue => $matchValue) {
        //                    if ($record->{$recordValue} != $match->{$matchValue}) {
        //                        $passes = false;
        //                    }
        //                }
        //
        //                return !$passes;
        //            });
        //        }
        //    }
        //}

        return $queries->merge($this->prepareQueryBlock($records, $relation));
    }

    /**
     * @param Dropper $relation
     * @param string $subQuery
     * @return Queries
     * @throws \Exception
     */
    protected function ImportDroppers(Dropper $relation, string $subQuery): Queries
    {
        $queries = new Queries();
        $select = "SELECT {$relation->getInput()} FROM {$relation->getTableName()} WHERE {$subQuery}";

        if (! $relation instanceof BeforeAfterCallbacks) {
            return $queries->append("DELETE FROM {$relation->getTableName()} WHERE {$subQuery};");
        }

        foreach ($relation->getBefore() as $item) {
            if ($item instanceof Dropper) {
                $queries->merge($this->ImportDroppers($item, "{$item->getReference()} IN ($select)"));
            }
        }

        $queries->append("DELETE FROM {$relation->getTableName()} WHERE {$subQuery};");

        foreach ($relation->getAfter() as $item) {
            if ($item instanceof Dropper) {
                $queries->merge($this->ImportDroppers($item, "{$item->getReference()} IN ($select)"));
            }
        }

        return $queries;
    }

    /**
     * @param Relation $model
     * @param Row $record
     *
     * @return Queries
     * @throws \Exception
     */
    private function retrieveJsonRelations(Relation $model, Row $record): Queries
    {
        $queries = new Queries();

        if (!str_contains($model->getInput(), '.')) {
            return $queries;
        }

        $explode = explode('.', $model->getInput());
        $field = $explode[0];

        $data = json_decode($record->property($field), true);
        $seq = implode('.', array_slice($explode, 1));

        if ($values = $data[$seq]) {
            if (!is_array($values)) {
                $values = array($values);
            }

            foreach ($values as $v) {
                switch (true) {
                    case ($model instanceof Model):
                        $queries->merge($this->ImportModel($model, $v, $record));
                        break;
                    case ($model instanceof Table):
                        $queries->merge($this->ImportRelation($model, $v, $record));
                        break;
                }
            }
        }

        return $queries;
    }

    /**
     * @param Collection $relations
     * @param Row $record
     *
     * @return Queries
     * @throws \Exception
     */
    protected function retrieveRelations(Collection $relations, Row $record): Queries
    {
        return $relations->reduce(function (Queries $queries, Relation $model) use ($record) {
            $input = $model->getInput();

            if (str_contains($input, '.')) {
                return $queries->merge($this->retrieveJsonRelations($model, $record));
            }

            switch (true) {
                case ($model instanceof Model && !is_null($value = $record->property($input))):
                    return $queries->merge($this->ImportModel($model, $value, $record));
                case ($model instanceof Table && !is_null($value = $record->property($input))):
                    return $queries->merge($this->ImportRelation($model, $value, $record));
                case ($model instanceof Dropper && !is_null($record->property($input))):
                    return $queries->merge($this->ImportDroppers($model, "{$model->getReference()} = {$record->property($input)}"));
            }

            return $queries;
        }, new Queries());
    }

    /**
     * Returns "REPLACE INTO ..." query for selected Row
     *
     * @param Row $record
     *
     * @return string
     */
    protected function getQueryFromRecord(Row $record) : string {
        $columns = [];
        $values = [];

        foreach ($record->data() as $attribute => $value) {
            //if ($tm = $this->manifest->getTableMutation($table) AND $tm->isColumnIgnored($attribute)) {
            //    continue;
            //}

            $columns[] = "`{$attribute}`";

            //if (
            //    $tableMutation = $this->builder->getManifest()->getTableMutation($table) AND
            //    $columnMutation = $tableMutation->getAttributeMutation($attribute)
            //) {
            //    $value = $columnMutation->getValueMutated();
            //}

            if (is_null($value)) {
                $value = 'NULL';
            } elseif(is_string($value)) {
                $value = sprintf( "'%s'", addslashes($value));
            }

            $values[] = $value;
        }

        return implode(' ', [
            'REPLACE',
            'INTO',
            $record->tableName(),
            "(" . implode(',', $columns) . ")",
            'VALUES',
            "(". implode(',', $values) .");"
        ]);
    }
}