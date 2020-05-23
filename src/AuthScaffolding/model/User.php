<?php

namespace App\Models;

use Simple\Model;
use function Simple\QueryBuilder\field;
use function Simple\bcrypt;

class User extends Model 
{
    /**
     * $table - table name using by this model
     * @var string
     */
    protected $table = 'users';

    /**
     * Fillables - the columns in you $table
     * @var array
     */ 
    protected $fillable = ['name', 'email', 'password_hash'];

    /**
     * @param string $email - Email of the user
     * @return mixed
     */
    public static function findByEmail($email)
    {
        $query = parent::factory()
        ->select()
        ->from('users')
        ->where(field('email')->eq($email))
        ->compile();
        return self::run($query,[
            'first'      => true,
            'fetch_mode' => 'FETCH_OBJ'
        ]);
    }
}