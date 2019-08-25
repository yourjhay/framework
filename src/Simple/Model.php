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
Use function Simple\QueryBuilder\field;

abstract class Model 
{

    protected $fillable;
    protected $table;
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
        switch(DBENGINE) {
            case 'mysqli':
            case 'mysql':
                return new QueryFactory(new QueryBuilder\Engine\MySqlEngine());
            break;
            case 'postgres':
                return new QueryFactory(new QueryBuilder\Engine\PostgresEngine());
            break;
            case 'sqlserver':
                return new QueryFactory(new QueryBuilder\Engine\SqlServerEngine());
            break;
            case 'common':
                return new QueryFactory(new QueryBuilder\Engine\CommonEngine());
            break;
            case 'basic':
                return new QueryFactory(new QueryBuilder\Engine\BasicEngine());
            break;
            default:
                return new QueryFactory(new QueryBuilder\Engine\MySqlEngine());
       }
    }

    /**
     * @param $query: Pass the Query object here to run
     * @param array $params: additional parameter
     * @return bool: Return false if query fails
     * @throws \Exception
     */
    public static function run($query, $params =[])
    {
        
        $method = explode(' ',$query->sql())[0];
        $stmt = self::DB()->prepare($query->sql());
        if(isset($params['fetch_mode'])) {
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
        } else {
            $stmt->setFetchMode(PDO::FETCH_CLASS,get_called_class());
        }
        $res = $stmt->execute($query->params());
        switch ($method)
        {
            case 'SELECT':
                return isset($params['first']) && $params['first']==true?$stmt->fetch():$stmt->fetchAll();
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

    /**
     * set properties
     *
     * @param [type] $name
     * @param [type] $value
     */
    public function __set($name, $value)
    {
        if(in_array($name, $this->fillable))
        {
            foreach($this->fillable as $fill)
            {
                if($name == $fill) {
                    $this->$name = $value;
                }
            }
        }
    }

    /**
     * get properties
     *
     * @param [type] $name
     * @return void
     */
    public function __get($name) 
    {
        return $this->$name;
    }

    /**
     * Save a data to fillable properties of the model
     *
     * @return void
     */
    public final function save()
    {
        $data=[];
        foreach($this->fillable as $fill) {
           if(isset($this->$fill)) {
            $data[$fill] = $this->$fill;
           } else {
            $data[$fill] = null;
           }
        }
        $table = $this->table;
        $q = self::factory()
        ->insert($table,$data)
        ->compile();
        self::run($q);
    }

}