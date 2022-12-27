<?php namespace Kickenhio\LaravelSqlSnapshot\Structure;

class EntrypointModel extends Relations\BeforeAfterCallbacks
{
    protected string $name;
    protected string $tableName;
    protected array $entrypoints;

    /**
     * @param string $model
     * @param string $tableName
     * @param array $entrypoints
     */
    public function __construct(string $model, string $tableName, array $entrypoints) {
        $this->name = $model;
        $this->tableName = $tableName;
        $this->entrypoints = $entrypoints;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return array
     */
    public function getEntryPoints(): array
    {
        return $this->entrypoints;
    }
}