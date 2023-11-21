<?php

namespace App\Models;

use Simple\Model;

class User extends Model
{
    /**
     * $table - table name using by this model
     * @var string
     */
    protected $table = 'users';

    /**
     * Fillables - the columns in your $table
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash'
    ];

    /**
     * @param string $email - Email of the user
     * @return mixed
     * @throws \Exception
     */
    public static function findByEmail(string $email)
    {
        return self::where('email', $email)->first();
    }
}
