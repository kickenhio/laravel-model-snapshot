<?php namespace Kickenhio\LaravelSqlSnapshot;

use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Kickenhio\LaravelSqlSnapshot\Query\SnapshotQueryDumpBuilder;
use Kickenhio\LaravelSqlSnapshot\Structure\DatabaseManifest;
use Kickenhio\LaravelSqlSnapshot\Structure\ManifestBuilder;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class LaravelSqlSnapshot
{
    protected ConfigRepository $config;

    /**
     * @param ConfigRepository $config
     */
    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $manifest
     *
     * @return SnapshotQueryDumpBuilder
     * @throws InvalidManifestSyntaxException
     */
    public function fromManifest(string $manifest): SnapshotQueryDumpBuilder
    {
        $manifestFile = $this->buildManifest(
            $this->config->get("snapshot.manifests.$manifest.file_path")
        );

        $connection = $this->config->get("snapshot.manifests.$manifest.connection");

        return new SnapshotQueryDumpBuilder($manifestFile, $connection);
    }

    /**
     * @param string $path
     *
     * @return DatabaseManifest
     * @throws InvalidManifestSyntaxException
     */
    public function buildManifest(string $path): DatabaseManifest
    {
        return (new ManifestBuilder($path))->build();
    }
}