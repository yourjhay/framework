<?php
namespace Simple\Tests\app;

\Simple\Config::set('database.engine', 'mysql');
\Simple\Config::set('database.server', 'localhost');
\Simple\Config::set('database.name', 'portfolio');
\Simple\Config::set('database.user', 'jhay');
\Simple\Config::set('database.pass', 'password');
\Simple\Config::set('app.name', 'Rey Jhon : Me');
\Simple\Config::set('app.description', 'The "Simply-PHP" Framework');
\Simple\Config::set('app.baseurl', '');
\Simple\Config::set('app.key', '');
\Simple\Config::set('security.show_errors', true);
\Simple\Config::set('security.error_handler', 'whoops');

class Model extends \Simple\Model
{
    protected $fillable =['fn'];
    protected $table = 'users';
}
