<?php 

namespace Simple\Security;

class SimplyEncrypt extends Encryption{
    
    /**
     * @param string string to be encrypted
     * @return string 
     */
    public static function encrypt($data) 
    {
       
        $ciphertext = self::baseEncrypt($data);
        return $ciphertext;
    }

}