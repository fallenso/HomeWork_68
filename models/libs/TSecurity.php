<?php
/**
 * 加解密套件
 * @author Fallen
 * @version 1.0
 */
class TSecurity
{

	// ====================================================================
	// openssl
	public static function decrypt($text, $key = DATA_HASH, $iv = DATA_HASH) {
		
		$text = base64_decode($text);
		return rtrim(openssl_decrypt($text, 'AES-128-CBC', $key, (OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING), $iv), "\0");
	}

	public static function encrypt($text, $key = DATA_HASH, $iv = DATA_HASH) {
		
		$text = str_pad($text, ceil(strlen($text) / 16) * 16, chr(0));
		$_base = base64_encode(openssl_encrypt($text, 'AES-128-CBC', $key, (OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING), $iv));
		return $_base;
	}
	
	// ====================================================================
	// mcrypt
	public static function decrypt_mcrypt($text, $key = DATA_HASH, $iv = DATA_HASH)
	{
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_CBC, $iv), "\t\0");
		//return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_CBC, $iv), "\0");
	}
	
	public static function encrypt_mcrypt()
	{
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_CBC, $iv));
	}

	// ====================================================================
	
	public static function hashcrypt($text) {
		return md5($text, true);
	}
	
	/*
	 * MD5 salt
	 */
	public static function MD5Encrypt($imsg, $isalt)
	{
		return md5($imsg.$isalt);
	}
	
	/**
	 * sha1 salt
	 */
	public static function Sha1Encrypt($imsg, $isalt = 'tronpy')
	{
		return sha1($imsg.$isalt);
	}
}
