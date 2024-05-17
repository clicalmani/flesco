<?php
namespace Clicalmani\Flesco\Http\Requests;

/**
 * HttpRequest class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 */
abstract class HttpRequest 
{
    /**
     * (non-PHPDoc)
     * @override
     */
    abstract public function render() : never;
}
