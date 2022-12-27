<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Relations;

use Kickenhio\LaravelSqlSnapshot\Contract\Relation;

class Table extends BeforeAfterCallbacks implements Relation
{
    protected string $tableName;
    protected string $input;
    protected string $reference;
    protected array $filters;

    public function __construct(
        string $tableName,
        string $input,
        string $reference,
        array $filters = array()
    ) {
        $this->tableName = $tableName;
        $this->input = $input;
        $this->reference = $reference;
        $this->filters = $filters;
    }

    public function getUnique(): string {
        return sprintf("%s.%s",
            get_class($this),
            $this->tableName
        );
    }

    public function getTableName(): string {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getInput(): string {
        return $this->input;
    }

    /**
     * @return string
     */
    public function getReference(): string {
        return $this->reference;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}