<?php

namespace Simple;
use Illuminate\Database\Eloquent\Model as EM;

/**
 * Main model class to extend Illuminate\Database\Eloquent\Model
 */
class Model extends EM 
{

    /**
     * @param $param - Table to be check
     * @param $column - lookup Column to check
     * @param $data - value to be compaire
     * @return bool
     */
    public static function unique_checker(array $param, string $column, string $data)
    {
        $table = $param[0];
        $ignore = isset($param[1]) ? $param[1] : null;
        $ignore_col = isset($param[2]) ? $param[2] : 'id';

        if (count($param) === 1) {
            $table = $param[0];
        } elseif (count($param) !== 3) {
            $ignore = $param[1];
        }

        $res = parent::table($table)->where($column, $data);

        if ($ignore) {
            $res = $res->where($ignore_col, '!', $ignore);
        }

        return $res->count();
    }
}
