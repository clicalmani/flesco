<?php
namespace Clicalmani\Flesco\Security;

use Clicalmani\Flesco\Exceptions\ValidationFailedException;

class Security {
	
	static function cleanStr($str) {
		
		$chars = sprintf ('%c..%c', 0, ord(0) - 1);
		$chars .= sprintf ('%c..%c', ord(9) + 1, ord('A') - 1);
		$chars .= sprintf ('%c..%c', ord('Z') + 1, ord('a') - 1);
		$chars .= sprintf ('%c..%c', ord('z') + 1, 255);
		
		$str = addcslashes($str, $chars);
		
		return $str;
	}
	
	/**
	 * Sanitize user inputs.
	 *
	 * @param Array $vars <br><br>
	 *     Array of inputs, usually global Arrays such as $_GET, $_POST, or $_REQUEST.
	 * @param Array $signatures <br><br>
	 * @param String $redirect [Optionnal] <br><br>
	 *     A redirect url if error.
	 *
	 * @return Array <br> Sanitized array.
	 */
	static function sanitizeVars($vars, $signatures, $redirect = NULL) 
	{
		$tmp = [];
		
		foreach ($signatures as $key => $sig) {
			if(!isset($vars[$key]) && isset($sig['required']) && $sig['required']) 
				throw new ValidationFailedException($key, $sig['required'], $redirect);
			else {
				if(isset($vars[$key])) {

					$tmp[$key] = $vars[$key];

					if(isset($sig['before'])){
						$tmp[$key] = $sig['before']($tmp[$key]);
					}

					if(isset($sig['type'])) {
						switch ($sig['type']) {
							case 'integer': settype($tmp[$key], 'integer'); break;
							case 'double': settype($tmp[$key], 'double'); break;
							case 'string': settype($tmp[$key], 'string'); break;
							case 'array': settype($tmp[$key], 'array'); break;
							case 'object': settype($tmp[$key], 'object'); break;
							case 'email': settype($tmp[$key], 'string'); break;
							case 'enum':
							case 'list': settype($tmp[$key], 'string'); break;
							case 'boolean':
							case 'bool': settype($tmp[$key], 'boolean'); break;
						}
						
						// Custom type check
						switch ($sig['type']) {
							case 'email':
								if (false == self::validateEmail($tmp[$key])) 
									throw new ValidationFailedException($key, $sig['required'], $redirect);
								break;

							case 'enum':
							case 'list':
								if ( !in_array($tmp[$key], $sig['list']) ) {
									if ( array_key_exists('default', $sig) ) $tmp[$key] = $sig['default'];
									else throw new ValidationFailedException($key, $sig['required'], $redirect);
								}
								break;

							case 'date':
								if (isset($sig['format'])) {
									if ( false == self::validateDate($tmp[$key], $sig['format']) ) {
										throw new ValidationFailedException($key, $sig['required'], $redirect);
									}
								} else throw new \Exception("Attribute `format` is required for `date` type");
								break;

							case 'datetime':
								if (isset($sig['format'])) {
									if ( false == self::validateDate($tmp[$key], $sig['format']) )
										throw new ValidationFailedException($key, $sig['required'], $redirect);
								} else throw new \Exception("Attribute `format` is required for `datetime` type");
								break;

							case 'regex':
								if (array_key_exists('pattern', $sig)) {
									if ( false == @ preg_match($sig['pattern'], $tmp[$key]) ) {
										if ( $sig['required'] ) throw new ValidationFailedException($key, $sig['required'], $redirect);
										else $tmp[$key] = null;
									}
								} else throw new \Exception("Attribute `pattern` is required for `regex` type, $key");
								break;
						}
						
						if(isset($sig['max'])) {
							switch ($sig['type']) {
								case 'integer':
								case 'double':
									if($tmp[$key] > $sig['max']) {
										$tmp[$key] = $sig['max'];
									}
									break;
								case 'string':
									if(strlen($tmp[$key]) > $sig['max']) {
										$tmp[$key] = substr($tmp[$key], 0, $sig['max']);
									}
									break;
							}
						}

						if (isset($sig['length']) AND $sig['type'] == 'string' AND strlen($tmp[$key]) !== $sig['length']) 
							if ($sig['required']) throw new \Exception("The data length is too long for attribute `$key`");
							else $tmp[$key] = null;

						if (isset($sig['nullable'])  AND $sig['nullable'] AND !$tmp[$key]) {
							$tmp[$key] = null;
						}
					}

					if(isset($sig['function']) && array_key_exists($key, $tmp)){
						$tmp[$key] = $sig['function']($tmp[$key]);
					}
				}
			}
		}
		return $tmp;
	}

	static function validateEmail($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	static function validateDate($date, $format)
	{
		$bindings = [
			'Y' => '[0-9]{4}',
			'm' => '[0-9]{2}',
			'd' => '[0-9]{2}',
			'H' => '[0-9]{2}',
			'i' => '[0-9]{2}',
			's' => '[0-9]{2}'
		];

		foreach ($bindings as $k => $v) {
			$format = str_replace($k, $v, $format);
		}
		
		return @ preg_match('/^' . trim($format) . '$/i', $date);
	}
    	
    static function hash($data, $method = '') 
	{
    	if ( empty($method) ) {
			$__func = function($str) {
				return password_hash($str, PASSWORD_DEFAULT);
			};
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
    
    static function createParametersHash($params) 
	{
    	$data = '';

    	foreach ($params as $key => $value){
    		$data .= $key . $value;
    	}
    	
    	return strtoupper( substr( self::hash($data, 'sha1'), strlen( self::iv() ), 10 ) );
    }

    static function verifyParameters() 
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

	static function iv()
	{
		return substr( hash('sha256', env('PASSWORD_CRYPT')), 0, 16);
	}
	
    static function opensslED($action, $string) {
	
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
    
    static function encrypt($value) { return self::opensslED('encrypt', $value); }

    static function decrypt($encrypted) { return self::opensslED('decrypt', $encrypted); }
}