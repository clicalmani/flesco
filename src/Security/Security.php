<?php
namespace Clicalmani\Flesco\Security;

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
	static function sanitizeVars($vars, $signatures, $redirect = NULL) {
		$tmp = array();

		foreach ($signatures as $key => $sig) {
			if(!isset($vars[$key]) && isset($sig['required']) && $sig['required']){
				if($redirect) {
					header("Location: $redirect");
				} else {
					echo "Parameter $key is missing, and there is no redirect url.";
				}
				exit();
			} else {
				if(isset($vars[$key])) {
					$tmp[$key] = $vars[$key];
					if(isset($sig['type'])) {
						switch ($sig['type']) {
							case 'integer': settype($tmp[$key], 'integer'); break;
							case 'double': settype($tmp[$key], 'double'); break;
							case 'string': settype($tmp[$key], 'string'); break;
							case 'array': settype($tmp[$key], 'array'); break;
							case 'object': settype($tmp[$key], 'object'); break;
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
					} else if(isset($sig['max'])) {
						if($redirect) {
							header("Location: $redirect");
						} else {
							echo "Max is set on a non-specified type.";
						}
						exit();
					}
					if(isset($sig['function'])){
						$tmp[$key] = $sig['function']($tmp[$key]);
					}
				}
			}
		}
		return $tmp;
	}
    	
    static function hash($data, $method = 'sha1') {
    	
    	if(!in_array($method, array('sha1', 'md5'))) {
    			die("the specified encryption function <<$method>> is not supported");
    	}
    	
    	$__func = $method;
    	
    	$_ipad = substr(self::SECRET_KEY, strlen(self::SECRET_KEY), 0) ^ str_repeat(chr(0x36), strlen(self::SECRET_KEY));
    	$__opad = substr(self::SECRET_KEY, 0, strlen(self::SECRET_KEY)) ^ str_repeat(chr(0x5C), strlen(self::SECRET_KEY));
    	
    	$__inner = pack('H32', $__func($_ipad . $data));
    	$__digest = $__func($__opad . $__inner);
    	
    	return strtoupper(substr($__digest, 0, 8));
    }
    
    static function createParameters($array) {
    	
    	$data = '';
    	$ret = array();
    	 
    	foreach ($array as $key => $value){
    		$data .= $key . $value;
    		$ret[] = "$key=$value";
    	}
    	
    	$hash = self::hash($data);
    	$ret[] = "hsh=$hash";
    	 
    	return join('&', $ret);
    }

    static function verifyParameters($array) {
    	
    	$data = '';
    	$ret = array();
    	$hash = isset($array['hsh'])? $array['hsh']: self::hash('');
    	unset($array['hsh']);
    	 
    	foreach ($array as $key => $value){
    		$data .= $key . $value;
    		$ret[] = "$key=$value";
    	}
    	
    	$hash = self::hash($data);
    	
    	if($hash != $hash){
    		return false;
    	} else {
    		return true;
    	}
    }
	
    static function opensslED($action, $string) {
	
		$output = false;
		$encrypt_method = "AES-256-CBC";
		
		// hash
		$key = hash('sha256', self::SECRET_KEY);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', self::SECRET_IV), 0, 16);
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