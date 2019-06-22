<?php
/**
 *  Example User Model
 *  All models to be created must extends base Model
 *  and include namespace App\Models.
 */
namespace App\Models;
Use Simple\Model;
Use function Latitude\QueryBuilder\field;
Use function Simple\bcrypt;
use PDO;

class User extends Model 
{

    public function save($data)
    {
        $query = parent::factory()
        ->insert('users',[
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => bcrypt($data['password'])
        ])
        ->compile();
        $stmt = static::DB()->prepare($query->sql());
        if($stmt->execute($query->params()))
            return true;
        return false;
    }    

    public static function findByEmail($email)
    {
        $query = parent::factory()
        ->select()
        ->from('users')
        ->where(field('email')->eq($email))
        ->compile();
        $stmt = static::DB()->prepare($query->sql());
        $stmt->execute($query->params());
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return json_decode(json_encode($result));
    }

}