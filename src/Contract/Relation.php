<?php namespace Kickenhio\LaravelSqlSnapshot\Contract;

interface Relation
{
    public function getUnique(): string;

    public function getInput(): string;
    
    public function getReference(): string;
}