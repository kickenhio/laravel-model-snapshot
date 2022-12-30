<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

use Illuminate\Support\Collection;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;

class SnapshotResult
{
    protected string $model;
    protected Collection $rows;
    protected array $proposals;

    public function __construct(Collection $rows, array $proposals = [])
    {
        $this->rows = $rows;
        $this->proposals = $proposals;
    }

    public function proposals(): array
    {
        return $this->proposals;
    }

    public function rows(): Collection
    {
        return $this->rows->map(function (ModelRetriever $retriever){
            return $retriever->getRow();
        });
    }

    /**
     * @return ModelRetriever|null
     */
    public function first(): ?ModelRetriever
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->rows->first();
    }

    public function last(): ?ModelRetriever
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->rows->last();
    }

    public function each(callable $callback): void
    {
        foreach ($this->rows as $row) {
            $callback($row);
        }
    }

    public function whenSingle(callable $callback): self
    {
        if ($this->isEmpty() || $this->count() > 1) {
            return $this;
        }

        $callback($this->first());

        return $this;
    }

    public function whenMany(callable $callback): self
    {
        if ($this->isEmpty() || $this->count() == 1) {
            return $this;
        }

        $callback($this->rows(), $this->proposals());

        return $this;
    }

    /**
     * @return Queries
     * @throws InvalidManifestSyntaxException
     */
    public function toSql(): Queries
    {
        $supervisor = new Supervisor();

        return $this->rows->reduce(function (Queries $carry, ModelRetriever $retriever) use ($supervisor) {
            return $carry->merge($retriever->withSupervisor($supervisor)->toSql(false));
        }, new Queries());
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function isEmpty(): bool
    {
        return $this->count() < 1;
    }
}