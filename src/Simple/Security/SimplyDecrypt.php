<?php 

namespace Simple\Security;

class SimplyDecrypt extends Encryption {
    
    /**
     * @param string $ciphertext CipherText to be decrypted
     * @return string
     * @throws \Exception 
     */
    public static function decrypt($ciphertext) 
    {
    
        try {

            $secret_data = self::baseDecrypt($ciphertext);
            return $secret_data;

        } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            // An attack! Either the wrong key was loaded, or the ciphertext has
            // changed since it was created -- either corrupted in the database or
            // intentionally modified by Eve trying to carry out an attack.
        
            // ... handle this case in a way that's suitable to your application ...
            throw new \Exception('Error: Wrong Key Or Modified Ciphertext');
        }
        
    }

    
}