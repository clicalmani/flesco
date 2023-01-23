<?php
namespace Clicalmani\Flesco\Http\Requests;

abstract class HttpRequest {
    abstract public static function render();

    public function validation($options = []) {
        //
    }

    public function validate() {
        //
    }
}