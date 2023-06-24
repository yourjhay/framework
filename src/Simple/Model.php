<?php

namespace Simple;

use PDO;
use Simple\QueryBuilder\QueryFactory;

use function Simple\QueryBuilder\field;

class Model extends BaseModel
{
    protected array $fillable;
    protected string $table;
    public int $id;
    
    /**
     * @param $param - Table to be check
     * @param $column - lookup Column to check
     * @param $data - value to be compaire
     * @return bool
     */
    public static function unique_checker(array $param, string $column, string $data)
    {
        $ignore=null;
        $ignore_col = null;
        $count = count($param);

        $table = $param[0];
        $ignore = isset($param[1]) ? $param[1] : null;
        $ignore_col = isset($param[2]) ? $param[2] : 'id';

        if ($count === 1) {
            $table = $param[0];
        } elseif ($count !== 3) {
            $ignore = $param[1];
        }

        $res = parent::table($table)->where($column,$data);
        if($ignore) {
            $res = $res->where($ignore_col,'!',$ignore);
        }
        $res = $res->count();

        return $res;
    }


    /**
     * set properties
     *
     * @param [type] $name
     * @param [type] $value
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->fillable))
        {
            foreach ($this->fillable as $fill)
            {
                if ($name == $fill) {
                    $this->$name = trim($value);
                }
            }
        }
        if($name=='id'){
            $this->id = $value;
        }
    }

    /**
     * get properties
     *
     * @param [type] $name
     * @return string
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     *  Fill data to fillable properties of the model and save/update database
     * @return \PDOStatement
     * @throws \Exception
     */
    public final function save()
    {
        $data=[];
        foreach ($this->fillable as $fill) {
           if (isset($this->$fill)) {
            $data[$fill] = $this->$fill;
           } else {
            $data[$fill] = null;
           }
        }
        
         if(isset($this->id)) {
            $this->update($data,['id'=>$this->id]);
         } else {
            $this->insert($data);
            $this->id = $this->con->id();
         }
        
         return $this;
    }

    
}
