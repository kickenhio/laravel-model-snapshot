<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Relations;

use Kickenhio\LaravelSqlSnapshot\Contract\Relation;

class Model implements Relation
{
    protected string $modelName;
    protected string $input;
    protected string $reference;
    protected string $ask;

    /**
     * @param string $modelName
     * @param string $input
     * @param string $reference
     * @param string $ask
     */
    public function __construct(string $modelName, string $input = 'id', string $reference = 'id', string $ask = '')
    {
        $this->modelName = $modelName;
        $this->reference = $reference;
        $this->input = $input;
        $this->ask = $ask;
    }
    
    /**
     * @return string
     */
    public function getUnique(): string
    {
        return sprintf("%s.%s",
            get_class($this),
            $this->modelName
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->modelName;
    }

    /**
     * @return string
     */
    public function getInput(): string
    {
        return $this->input;
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return string
     */
    public function getAsk(): string
    {
        return $this->ask;
    }
}