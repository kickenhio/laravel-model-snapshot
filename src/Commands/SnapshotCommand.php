<?php namespace Kickenhio\LaravelSqlSnapshot\Commands;

use Illuminate\Console\Command;
use Kickenhio\LaravelSqlSnapshot\Exceptions\InvalidManifestSyntaxException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Kickenhio\LaravelSqlSnapshot\Facades\Snapshot as SnapshotSQL;

class SnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-snapshot:dump {manifest} {model} {id*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves SQL queries to replicate DB entries';

    /**
     * @return int
     */
    public function handle(): int
    {
        try {
            $snapshot = SnapshotSQL::fromManifest($this->argument('manifest'));

            foreach ($this->argument('id') as $id) {
                foreach ($snapshot->retrieve($this->argument('model'), $id)->toSql() as $query) {
                    if (substr($query, 0, 2) !== '--') {
                        $this->getOutput()->text($query . PHP_EOL);
                    }
                };
            }

        } catch (InvalidManifestSyntaxException $e) {
            $this->output->error($e);
        }

        return CommandAlias::SUCCESS;
    }
}