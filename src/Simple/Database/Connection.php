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

    private $capsule = null;

    /**
     * Initiate Connection using capsule;
     *
     * @return void
     */
    public function connect()
    {      
        if (!$this->capsule) {
            $capsule = new Capsule;
            $capsule->addConnection
            ([
                'driver'    => DBENGINE,
                'host'      => DBSERVER,
                'database'  => DBNAME,
                'username'  => DBUSER,
                'password'  => DBPASS,
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'   => '',
            ]); 

            // Set the event dispatcher used by Eloquent models... (optional)
           
            $capsule->setEventDispatcher(new Dispatcher(new Container));

            // Make this Capsule instance available globally via static methods... (optional)
            $capsule->setAsGlobal();

            $capsule->bootEloquent();
            $this->capsule = $capsule;
        }  
    }
}