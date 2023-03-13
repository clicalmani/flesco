<?php
namespace Clicalmani\Flesco\Http\Requests;

interface RequestInterface {
    
    public static function render();

    public function validate();
}