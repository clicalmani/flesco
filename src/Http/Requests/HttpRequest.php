<?php
namespace Clicalmani\Flesco\Http\Requests;

abstract class HttpRequest {
    abstract public static function render();

    /**
     * @deprecated
     */
    public function validation($options = []) {
        //
    }

    /**
     * @deprecated
     */
    public function validate() {
        //
    }
}