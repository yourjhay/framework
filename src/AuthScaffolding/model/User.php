<?php

namespace App\Models;

use Simple\Model;

use function Simple\QueryBuilder\field;

class User extends Model 
{
    /**
     * $table - table name using by this model
     * @var string
     */
    protected string $table = 'users';

    /**
     * Fillables - the columns in your $table
     * @var array
     */ 
    protected array $fillable = [
        'name',
        'email',
        'password_hash'
    ];

    /**
     * @param string $email - Email of the user
     * @return mixed
     * @throws \Exception
     */
    public static function findByEmail($email)
    {
        return parent::select()
            ->only($email,'email');
    }
}