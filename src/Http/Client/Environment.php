<?php
namespace Clicalmani\Flesco\Http\Client;

/**
 * Interface Environment
 * 
 * Describes a domain that hosts a REST API, against which an HttpClient will make requests.
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
interface Environment
{
    /**
     * Return the base url
     * 
     * @return string
     */
    public function baseUrl() : string;
}