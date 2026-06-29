<?php

namespace Simple\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * Trait for Capsule Connetion
 */
trait Connection
{

    public ?Capsule $capsule = null;

    /**
     * Initiate Connection using capsule;
     *
     * @return void
     */
    public function connect()
    {
        if (!$this->capsule) {
            $capsule = new Capsule;
            $dbConfig = [
                'driver'    => \Simple\Config::get('database.engine', 'sqlite'),
                'host'      => \Simple\Config::get('database.server', 'localhost'),
                'database'  => \Simple\Config::get('database.name', './database/database.db'),
                'username'  => \Simple\Config::get('database.user', 'root'),
                'password'  => \Simple\Config::get('database.pass', ''),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'   => '',
            ];

            if ($dbConfig['driver'] === 'sqlite') {
                $database = $dbConfig['database'];

                if ($database !== ':memory:' && $database[0] !== '/') {
                    $root = \Simple\Config::get('app.project_root', getcwd());
                    $database = $root . '/' . ltrim($database, './');
                }

                if ($database !== ':memory:' && !file_exists($database)) {
                    $dir = dirname($database);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    touch($database);
                }

                $dbConfig['database'] = $database;
            }

            $capsule->addConnection($dbConfig);

            // Set the event dispatcher used by Eloquent models... (optional)
            $capsule->setEventDispatcher(new Dispatcher(new Container));

            // Make this Capsule instance available globally via static methods... (optional)
            $capsule->setAsGlobal();

            $capsule->bootEloquent();
            $this->capsule = $capsule;
        }
    }
}
