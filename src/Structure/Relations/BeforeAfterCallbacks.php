<?php namespace Kickenhio\LaravelSqlSnapshot\Structure\Relations;

use Illuminate\Support\Collection;
use Kickenhio\LaravelSqlSnapshot\Contract\Relation;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;

abstract class BeforeAfterCallbacks
{
    protected array $before = [];
    protected array $after = [];

    /**
     * @param array $functions
     *
     * @return void
     * @throws InvalidManifestSyntaxException
     */
    public function registerBefore(array $functions): void
    {
        foreach ($functions as $function) {
            if (!is_null($relation = $this->getRelation($function))) {
                $this->before[] = $relation;
            }
        }
    }

    /**
     * @return Collection
     */
    public function getBefore(): Collection
    {
        return new Collection($this->before);
    }

    /**
     * @return Collection
     */
    public function getAfter(): Collection
    {
        return new Collection($this->after);
    }

    /**
     * @param array $functions
     *
     * @return void
     * @throws InvalidManifestSyntaxException
     */
    public function registerAfter(array $functions): void
    {
        
        foreach ($functions as $function) {
            if (!is_null($relation = $this->getRelation($function))) {
                $this->after[] = $relation;
            }
        }
    }

    /**
     * @param array $item
     *
     * @return Relation
     * @throws InvalidManifestSyntaxException
     */
    protected function getRelation(array $item) : Relation {

        $relation = null;
        $input = $item['input'];
        $reference = $item['reference'] ?? 'id';
        $method = $item['method'];

        if ($method == 'related') {
            $relation = new Table($item['table'], $input, $reference, $item['filters'] ?? []);
        } elseif ($method == 'droppers') {
            $relation = new Dropper($item['table'], $input, $reference);
        } elseif ($method == 'model') {
            $relation = new Model($item['model'], $input, $reference, $item['ask'] ?? '');
        }

        if (is_null($relation)) {
            throw new InvalidManifestSyntaxException("Unrecognized relation type '$method'");
        }

        if ($relation instanceof self) {
            $relation->registerBefore($item['before'] ?? []);
            $relation->registerAfter($item['after'] ?? []);
        }

        return $relation;
    }
}