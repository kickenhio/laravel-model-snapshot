<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Mutations;

use Illuminate\Support\Collection;

class TableDataMutations
{
    protected string $tableName;
    protected Collection $ignoreColumns;
    protected Collection $columnMutations;

    /**
     * @param string $tableName
     */
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->ignoreColumns = collect();
        $this->columnMutations = collect();
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $columnName
     * @return void
     */
    public function addIgnoreColumn(string $columnName) : void {
        $this->ignoreColumns->push($columnName);
    }

    /**
     * @return Collection
     */
    public function getIgnoreColumns(): Collection {
        return $this->ignoreColumns->unique();
    }

    /**
     * @param string $columnName
     * @return bool
     */
    public function isColumnIgnored(string $columnName): bool
    {
        return $this->getIgnoreColumns()->contains($columnName);
    }

    /**
     * @param ManifestAttributeMutation $mutation
     * @return void
     */
    public function addAttributeMutation(ManifestAttributeMutation $mutation): void {
        $this->columnMutations->put($mutation->getColumnName(), $mutation);
    }

    /**
     * @param string $column
     * @return ManifestAttributeMutation|null
     */
    public function getAttributeMutation(string $column): ?ManifestAttributeMutation
    {
        return $this->columnMutations->get($column);
    }
}