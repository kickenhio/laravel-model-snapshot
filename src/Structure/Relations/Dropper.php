<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Relations;

use Kickenhio\LaravelSqlSnapshot\Contract\Relation;

class Dropper extends BeforeAfterCallbacks implements Relation {

    protected string $tableName;
    protected string $input;
    protected string $reference;

    /**
     * @param string $tableName
     * @param string $relatedColumn
     * @param string $reference
     */
    public function __construct(string $tableName, string $input, string $reference) {
        $this->tableName = $tableName;
        $this->input = $input;
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getUnique(): string {
        return sprintf("%s.%s",
            get_class($this),
            $this->tableName
        );
    }

    /**
     * @return string
     */
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
}