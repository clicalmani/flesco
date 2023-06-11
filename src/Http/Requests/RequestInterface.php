<?php
namespace Clicalmani\Flesco\Http\Requests;

interface RequestInterface {
    
    /**
     * Render request result
     */
    public static function render();

    /**
     * Validate request parameters
     */
    public function validate();

    /**
     * Prepare request for validation
     */
    public function prepareForValidation();
}