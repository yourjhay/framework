<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** BASE MODEL Class ***
------------------------------------------------------------------*/
namespace Simple;
Use PDO;
Use Latitude\QueryBuilder\Engine\MySqlEngine;
Use Latitude\QueryBuilder\QueryFactory;

abstract class Model 
{

    public static $db;
    /**
     * GET the PDO connection
     * @return mixed
     */
    protected static function DB() 
    {
        self::$db = null;
        if(self::$db===null) {
            self::$db = new PDO("mysql:host=".DBSERVER.";dbname=".DBNAME.";charset=utf8",DBUSER, DBPASS);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return self::$db;
        }
    }

    /**
     * Instantiate Latitude query Builder. for more info
     * https://latitude.shadowhand.me/
     * 
     * For the meantime we're using a third-party library 
     * while developing simple-php's own query builder
     * @return object
     */
    public static function factory() 
    {
        return new QueryFactory(new MySqlEngine());
    } 
    
}