<?php

namespace Simple\Security;

require_once('defuse-crypto.phar');

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

class Encryption {

    public static function loadEncryptionKeyFromConfig()
    {
        $keyAscii = APP_KEY;
        return Key::loadFromAsciiSafeString($keyAscii);
    }

    public static function generateKey()
    {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }

    public static function baseDecrypt($ciphertext) 
    {
        $key = self::loadEncryptionKeyFromConfig();
        return Crypto::decrypt($ciphertext,$key);
    }

    public static function baseEncrypt($data) 
    {
        $key = self::loadEncryptionKeyFromConfig();
        return Crypto::encrypt($data,$key);
    }
}