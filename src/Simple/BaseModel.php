<?php

namespace Simple;

use Closure;
use Medoo\Medoo;
use PDO;
use PDOStatement;

class BaseModel
{

    public static array $where = [];
    public static array $temp_where = [];
    public static array $or_where = [];
    public static $columns = '*';
    public static array $orderBy = [];
    public static array $join = [];
    public static ?string $set_table = null;



    /**
     * @var mixed|string
     */
    protected string $table;

    /**
     *  Medoo Connection instance
     *
     * @var Medoo [type]
     */
    public Medoo $con;

    public function __construct()
    {
        $this->con = new Medoo([
            'type' => DBENGINE,
            'host' => DBSERVER,
            'database' => DBNAME,
            'username' => DBUSER,
            'password' => DBPASS,
            'error' => SHOW_ERRORS ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
            'testMode' => DBTESTMODE
        ]);
    }

    /**
     * @param null $values
     * @return BaseModel
     */
    public static function table(string $table): BaseModel
    {
        self::$set_table = $table;
        return new static;
    }

    /**
     * @param null $values
     * @return BaseModel
     */
    public static function select($values =  null): BaseModel
    {
        self::$columns = $values ?? '*';
        return new static;
    }

    /**
     * Return only 1 record
     * @param string|null $col
     * @param mixed $val
     * @return bool|object
     */
    public static function only($val, string $col = null)
    {
        $cl = get_called_class();
        $t = (new $cl);
        $t->compileWhere();
        $columns = self::$columns;
        $where[$col ?? 'id'] = $val;
        $table = $t->table ?? $t->getClass();
        $data = ($t->con->get($table, $columns, $where));
        if($data) {
            foreach($data as $key => $dt) {
                $t->$key = $dt;
            }
        } else {
            return false;
        }
        return $t;
    }

    /**
     * return called class function
     * @return string
     */
    public function getClass(): string
    {
        $class = get_called_class();
        return self::$set_table ?? strtolower($class) . 's';
    }

    public static function where($column, $operator = null, $value = null): BaseModel
    {
        $default = func_num_args();
        [$value, $operator] = self::prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $closure = is_object($column);
        if (!$closure) {
            if ($default === 2) {
                self::$temp_where[$column . ' #' . uniqid()] = $value;
            } else {
                $column = "$column" . '[' . $operator . ']';
                self::$temp_where[$column] = $value;
            }
        }

        if ($closure && is_null($operator)) {
            return self::whereNested($column);
        }
        return new static;
    }

    public static function prepareValueAndOperator($value, $operator, $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        }
        return [$value, $operator];
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param Closure $callback
     * @return $this
     */
    public static function whereNested(Closure $callback): BaseModel
    {
        call_user_func($callback, $query = new static);
        return new static;
    }

    /**
     * @param $column
     * @param array $value
     * @return BaseModel
     */
    public static function whereBetween($column, array $value): BaseModel
    {
        $column = "$column" . '[<>]';
        self::$temp_where[$column] = $value;
        return new static;
    }

    /**
     * @param $column
     * @param array $value
     * @return BaseModel
     */
    public static function whereNotBetween($column, array $value): BaseModel
    {
        array_push(self::$temp_where, [
            "$column" . '[><]' => $value
        ]);
        return new static;
    }

    /**
     * Insert function
     * https://medoo.in/api/insert
     *
     * @param array $values
     * @return PDOStatement
     */
    public function insert(array $values): PDOStatement
    {
        $table = $this->table ?? $this->getClass();
        return $this->con->insert($table,  $values);
    }

    /**
     * Update function
     * https://medoo.in/api/update
     *
     * @param array $values
     * @param array $where
     * @return PDOStatement
     */
    public function update(array $values , array $where): PDOStatement
    {
        $table = $this->table ?? $this->getClass();
        return $this->con->update($table,  $values, $where);
    }


    /**
     * Undocumented function
     *
     * @param [type] $column
     * @param string $sort
     * @return $this
     */
    public static function orderBy($column, string $sort = 'DESC'): BaseModel
    {
        self::$orderBy[$column] = $sort;
        return new static;
    }

    public function orWhere(...$args): BaseModel
    {
        $closure = $args[1] instanceof Closure;
        if (!$closure) {

            $column = $args[0];
            $operator = $args[1];
            $value = count($args) > 2 ? $args[2] : $args[1];

            if (isset(self::$or_where[$column])) {
                $column = $column . ' #' . uniqid();
            }

            if (count($args) >= 3) {
                $column = "$column" . '[' . $operator . ']';
            }
            self::$or_where[$column] = $value;
        }
        return new static;
    }

    /**
     * @param $limit
     * @param bool $debug
     * @return array
     * @throws \Exception
     */
    public function paginate($limit, bool $debug = false): array
    {
        $data['total'] = (int)$this->count('id');
        $page = (int)isset($_GET['page']) ? $_GET['page'] : 1;
        $_page =(int) $page;
        if ($page < 0) $page = 1;
        $page -= 1;

        $offset = $page * $limit;
        $data['current_page'] = (int)$page + 1;
        $data['perpage'] = (int)$limit;
        $data['data'] = $this->limit($offset, $limit)->get($debug);
        $data['last_page'] = (int)ceil($data['total'] / $limit);
        if($_page > $data['last_page'] && $data['last_page'] > 0) {
            throw new \Exception("PAGE NOT FOUND", 404);
        }
        return $data;

    }

    /**
     * @return int|null
     */
    public function count(): ?int
    {
        $table = $this->table ?? $this->getClass();
        self::compileWhere();
        $where = self::$where;
        return $this->con->count($table, $where);
    }

    public function compileWhere()
    {
        $where_array = self::$temp_where;
        $or_array = self::$or_where;
        $orderBy = self::$orderBy;

        self::$where = $where_array;

        if (count($or_array) > 1) {
            self::$where['OR'] = $or_array;
        }

        if (count($or_array) === 1 && count($where_array) === 1) {
            self::$where = [];
            $arr = array_merge($where_array, $or_array);
            self::$where['OR'] = $arr;
        }

        if (count($or_array) > 1 && count($where_array) === 1) {
            self::$where['OR'] = $or_array;
        }

        if (count($where_array) > 1 && count($or_array) === 1) {
            self::$where = [];
            $and = $where_array;
            self::$where['OR'] = $or_array;
            self::$where['OR']['AND'] = $and;
        }

        if(!empty($orderBy)) {
            self::$where['ORDER'] = $orderBy;
        }
    }

    /**
     * Left Join function
     * OUTPUT: LEFT JOIN "account" ON "post"."author_id" = "account"."user_id"
     *
     * @param string $table Table to be join
     * @param string $column column from the join table
     * @param string $column2 column of the current table you want to compare
     * @return $this
     */
    public static function leftJoin($table, $column, $column2): BaseModel
    {
        $key_table = '[>]'.$table;
        self::$join[$key_table] = [$column=>$column2];
        return new static;
    }

    /**
     * Inner Join function
     * OUTPUT: INNER JOIN "account" ON "post"."author_id" = "account"."user_id"
     * @param string $table Table to be join
     * @param string $column column from the join table
     * @param string $column2 column of the current table you want to compare
     * @return $this
     */
    public static function join($table, $column, $column2): BaseModel
    {
        $key_table = '[><]'.$table;
        self::$join[$key_table] = [$column2=>$column];
        return new static;
    }

    /**
     * Compile query and return record
     * @param bool|callable $debug
     * @return array|null
     */
    public function get($debug = false)
    {
        self::compileWhere();
        $columns = self::$columns;
        $where = self::$where;
        $table = $this->table ?? $this->getClass();
        $con = $debug === true ? $this->con->debug() : $this->con;
        if(!empty(self::$join)) {
            $join = self::$join;
        return $con->select($table, $join,
            $columns,
            $where);
        }
        if(is_callable($debug)) {
            return $con->select($table,
                $columns,
                $where, $debug);
        }
        return $con->select($table,
            $columns,
            $where);
    }

    /**
     *  use to set offset and limit on record
     * @param int $offset
     * @param null $limit
     * @return $this
     */
    public function limit(int $offset = 0, $limit = null): BaseModel
    {
        $limit = func_num_args() === 1 ? $offset : $limit;
        $offset = func_num_args() === 1 ? 0 : $offset;
        self::$temp_where['LIMIT'] = [$offset, $limit];
        return new static;
    }
}
