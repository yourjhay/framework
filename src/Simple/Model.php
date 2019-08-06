<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** BASE MODEL Class ***
------------------------------------------------------------------*/
namespace Simple;
Use PDO;
Use Simple\QueryBuilder\Engine\MySqlEngine;
Use Simple\QueryBuilder\QueryFactory;

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
     * Instantiate Simply query Builder. for more info
     * https://latitude.shadowhand.me/
     *
     * @return object
     */
    public static function factory() 
    {
        return new QueryFactory(new MySqlEngine());
    }

    /**
     * @param $query: Pass the Query object here to run
     * @param bool $first: if true it will only return the first object
     * @return bool: Return false if query fails
     * @throws \Exception
     */
    public static function run($query, $first=false)
    {
        $method = explode(' ',$query->sql())[0];
        $stmt = self::DB()->prepare($query->sql());
        $stmt->setFetchMode(PDO::FETCH_CLASS,get_called_class());
        $res = $stmt->execute($query->params());
        switch ($method)
        {
            case 'SELECT':
                return $first==true?$stmt->fetch():$stmt->fetchAll();
                break;
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
                return $res !== false;
                break;
            default:
                throw new \Exception("Query format is not define", 500);
        }
    }

    /**
     * @param $table - Table to be check
     * @param $column - lookup Column to check
     * @param $data - value to be compaire
     * @return bool
     */
    public static function unique_checker($table, $column, $data)
    {
        $sql = "SELECT $column FROM $table WHERE $column = ?";
        $stmt = self::DB()->prepare($sql);
        $stmt->execute(array($data));
        return $stmt->fetch() !== false;
    }
}