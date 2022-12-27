<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Mutations;

use Faker\Factory;

class ManifestAttributeMutation
{
    protected string $columnName;
    protected string $method;
    protected $value;

    /**
     * @param string $columnName
     * @param string $method
     * @param mixed $value
     */
    public function __construct(string $columnName, string $method, $value = null)
    {
        $this->columnName = $columnName;
        $this->method = $method;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getColumnName(): string {
        return $this->columnName;
    }

    public function getValueMutated()
    {
        switch ($this->method) {
            case 'faker': return $this->getFakerValue();
            case 'static': return $this->getStaticValue();
        }

        return $this->value;
    }

    public function getStaticValue() {
        return $this->value;
    }

    public function getFakerValue() {
        $faker = Factory::create();

        try {
            $result = $faker->{$this->value}();
        } catch (\InvalidArgumentException $x) {
            return null;
        }

        return $result;
    }
}