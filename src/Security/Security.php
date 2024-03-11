<?php
namespace Clicalmani\Flesco\Security;

use Clicalmani\Flesco\Exceptions\ValidationFailedException;

class Security 
{
	/**
	 * Generate a data hash
	 * 
	 * @param mixed $data
	 * @param ?string $method
	 * @return mixed
	 */
    public static function hash(mixed $data, ?string $method = '') : mixed
	{
    	if ( '' === $method ) {
			$__func = fn($str) => password_hash($str, PASSWORD_DEFAULT);
		} else {
			$__func = $method;
		}

		$__secret = env('APP_KEY');
    	
    	$_ipad = substr($__secret, strlen($__secret), 0) ^ str_repeat(chr(0x36), strlen($__secret));
    	$__opad = substr($__secret, 0, strlen($__secret)) ^ str_repeat(chr(0x5C), strlen($__secret));
    	
    	$__inner = @ pack('H32', $__func($_ipad . $data));
    	$__digest = $__func($__opad . $__inner);
    	
    	return $__digest;
    }
    
	/**
	 * Create parameters hash
	 * 
	 * @param array $params
	 * @return string
	 */
    public static function createParametersHash(array $params) : string
	{
    	$data = '';

    	foreach ($params as $key => $value){
    		$data .= $key . $value;
    	}
    	
    	return strtoupper( substr( self::hash($data, 'sha1'), strlen( self::iv() ), 10 ) );
    }

	/**
	 * Verify parameters
	 * 
	 * @return bool
	 */
    public static function verifyParameters() : bool
	{
    	$data = '';
		
    	$request_hash = isset($_REQUEST['hash'])? $_REQUEST['hash']: '';
		
    	unset($_REQUEST['hash']);
    	 
    	foreach ($_REQUEST as $key => $value){
    		$data .= $key . $value;
    	}
		
		$hash = strtoupper( substr( self::hash($data, 'sha1'), strlen( self::iv() ), 10 ) );
    	
    	if($request_hash === $hash){
    		return true;
    	}

		return false;
    }

	/**
	 * Create iv
	 * 
	 * @return string
	 */
	public static function iv() : string
	{
		return substr( hash('sha256', env('PASSWORD_CRYPT')), 0, 16);
	}
	
	/**
	 * Openssl Encrypt or decrypt a string
	 * 
	 * @param string $action
	 * @param string $string
	 * @return mixed
	 */
    public static function opensslED(string $action, string $string) : mixed
	{
	
		$output = false;
		$encrypt_method = "AES-256-CBC";
		
		// hash
		$key = hash('sha256', $_ENV['APP_KEY']);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = self::iv();

		if ( $action == 'encrypt' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		} else if( $action == 'decrypt' ) {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
		return $output;
	}
    
	/**
	 * Encrypt a value
	 * 
	 * @param string $value
	 * @return mixed
	 */
    public static function encrypt(string $value) { return self::opensslED('encrypt', $value); }

	/**
	 * Decrypt an encrypted value
	 * 
	 * @param string $value
	 * @return mixed
	 */
    public static function decrypt(string $value) : mixed { return self::opensslED('decrypt', $value); }
}
