<?php
namespace Simple\Tests\app;

/**
 * Application Configuration Settings
 */
defined('APP_NAME') ? null : define('APP_NAME', 'Rey Jhon : Me');
defined('APP_DESCRIPTION') ? null : define('APP_DESCRIPTION', 'The "Simply-PHP" Framework');
defined('BASEURL') ? null : define('BASEURL', '');
defined('APP_KEY') ? null : define('APP_KEY', '');

/**
 * Error handling behaviour
 * Set to false in production
 */
defined('SHOW_ERRORS') ? null : define('SHOW_ERRORS', true);

/**
 * Options:
 *  simply - use the default error handling template.
 *  whoops - use "filp/whoops" error handling library.
 *  **IF you set whoops as default ERROR_HANDLER you need to install it.
 *    run: composer require filp/whoops
 */
defined('ERROR_HANDLER') ? null : define('ERROR_HANDLER', 'whoops');

defined('DBENGINE') ? null : define('DBENGINE', 'mysql');

defined('DBSERVER') ? null : define('DBSERVER', 'localhost');
defined('DBUSER') ? null : define('DBUSER', 'jhay');
defined('DBPASS') ? null : define('DBPASS', 'password');
defined('DBNAME') ? null : define('DBNAME', 'portfolio');
defined('DBTESTMODE') ? null : define('DBTESTMODE', false);



class Model extends \Simple\Model
{
    protected $fillable =['fn'];
    protected $table = 'users';
}
