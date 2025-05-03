<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tables in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [];
        
        // Different method based on database driver
        $connection = config('database.default');
        
        switch ($connection) {
            case 'mysql':
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $values = get_object_vars($table);
                    $this->line(reset($values));
                }
                break;
                
            case 'sqlite':
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($tables as $table) {
                    $this->line($table->name);
                }
                break;
                
            case 'pgsql':
                $tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'");
                foreach ($tables as $table) {
                    $this->line($table->tablename);
                }
                break;
                
            default:
                $this->error("Unsupported database driver: {$connection}");
                return 1;
        }
        
        return 0;
    }
} 