<?php namespace Kickenhio\LaravelSqlSnapshot\Facades;

use Illuminate\Support\Facades\Facade;
use Kickenhio\LaravelSqlSnapshot\Query\SnapshotQueryDumpBuilder;

/**
 * @method static SnapshotQueryDumpBuilder fromManifest($string)
 */
class Snapshot extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'kickenhio.snapshot';
    }
}