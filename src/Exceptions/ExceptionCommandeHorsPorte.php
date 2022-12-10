<?php
namespace src\Exceptions;

use Exceptions\ExceptionRequete;

class ExceptionCommandeHorsPorte extends ExceptionRequete {
	function __construct($message){
		parent::__construct($message);
	}
	
	function message() {
		return COMMANDE_HORS_PORTE_MESSAGE;
	}
}