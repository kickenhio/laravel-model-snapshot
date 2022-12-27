<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

use Illuminate\Support\Collection;

class Supervisor
{
    protected Collection $processed;

    public function __construct()
    {
        $this->processed = new Collection();
    }

    /**
     * @return Collection
     */
    public function getProcessed(): Collection
    {
        return $this->processed;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function alreadyProcessed(string $key): bool
    {
        return $this->processed->contains($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function mark(string $key): bool
    {
        if ($this->alreadyProcessed($key)) {
            return true;
        }

        $this->processed->push($key);
        return false;
    }
}