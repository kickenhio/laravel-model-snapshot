<?php namespace Kickenhio\LaravelSqlSnapshot\Structure;

class EntrypointModel extends Relations\BeforeAfterCallbacks
{
    protected string $name;
    protected string $tableName;
    protected array $entrypoint;

    /**
     * @param string $model
     * @param string $tableName
     * @param array $entrypoint
     */
    public function __construct(string $model, string $tableName, array $entrypoint) {
        $this->name = $model;
        $this->tableName = $tableName;
        $this->entrypoint = $entrypoint;
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
        return $this->entrypoint;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function getEntrypoint(string $field): array
    {
        if (is_array($this->entrypoint[$field])) {
            return $this->entrypoint[$field];
        }

        return [
            'column' => $this->entrypoint[$field],
            'dupeInfo' => []
        ];
    }
}