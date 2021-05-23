<?php
namespace Simple\Tests\app;

/**
 * Application Configuration Settings
 */
define('APP_NAME', 'Rey Jhon : Me');
define('APP_DESCRIPTION', 'The "Simply-PHP" Framework');
define('BASEURL', '');
define('APP_KEY', '');

/**
 * Error handling behaviour
 * Set to false in production
 */
define('SHOW_ERRORS', true);

/**
 * Options:
 *  simply - use the default error handling template.
 *  whoops - use "filp/whoops" error handling library.
 *  **IF you set whoops as default ERROR_HANDLER you need to install it.
 *    run: composer require filp/whoops
 */
define('ERROR_HANDLER', 'whoops');

define('DBENGINE', 'mysql');

define('DBSERVER', 'localhost');
define('DBUSER', 'jhay');
define('DBPASS', 'password');
define('DBNAME', 'portfolio');
define('TESTMODE', true);



class Model extends \Simple\Model
{
    protected array $fillable =['fn'];
    protected string $table = 'users';
}
