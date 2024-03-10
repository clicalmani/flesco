<?php
namespace Clicalmani\Flesco\Security;

use Clicalmani\Flesco\Exceptions\ValidationFailedException;

class Security 
{
	/**
	 * Sanitize user inputs.
	 *
	 * @param array $vars <br><br>
	 *     Array of inputs, usually global Arrays such as $_GET, $_POST, or $_REQUEST.
	 * @param array $signatures <br><br>
	 * @param string $redirect [Optionnal] <br><br>
	 *     A redirect url if error.
	 *
	 * @return array <br> Sanitized array.
	 */
	public static function sanitizeVars(array $vars, array $signatures, ?string $redirect = NULL) 
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

	/**
	 * Validate email input
	 * 
	 * @param string $email
	 * @return mixed
	 */
	public static function validateEmail(string $email) : mixed
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Validate date input
	 * 
	 * @param string $date
	 * @param string $format
	 * @return int|false
	 */
	public static function validateDate(string $date, string $format) : int|false
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
