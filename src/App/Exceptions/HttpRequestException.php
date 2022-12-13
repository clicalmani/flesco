<?php
namespace Clicalmani\Flesco\App\Exceptions;

class HttpRequestException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>