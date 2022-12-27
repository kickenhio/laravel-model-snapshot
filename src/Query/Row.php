<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

class Row
{
    protected array $data;
    protected string $table;
    protected ?Row $parent;

    public function __construct(string $table, array $data, Row $parent = null)
    {
        $this->data = $data;
        $this->table = $table;
        $this->parent = $parent;
    }

    public function tableName(): string
    {
        return $this->table;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function parent(): ?Row
    {
        return $this->parent;
    }

    public function property(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function id() : ?int
    {
        return $this->property('id');
    }

    public function fingerprint(): string
    {
        return md5($this->tableName() . ':' . $this->id() ?? json_encode($this->data()));
    }
}