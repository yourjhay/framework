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
    public static function unique_checker($param, $column, $data)
    {
        $ignoreQuery='';

        if(count($param) == 1){
            $table = $param[0];
        } else {
            $table = $param[0];
            $ignoreThis = $param[1];
            $ignoreQuery = "OR id != $ignoreThis";
        }

        $sql = "SELECT $column FROM $table WHERE $column = ? $ignoreQuery";
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
