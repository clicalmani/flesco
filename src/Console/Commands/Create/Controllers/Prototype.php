<?php
namespace App\Http\Controllers;

use Clicalmani\Flesco\Http\Controllers\RequestController as Controller;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Resources\Views\View;

class ClassName extends Controller

{
    /**
     * |----------------------------------------------------------------
     * |                ***** Example *****
     * |----------------------------------------------------------------
     * |
     * 
     * Render a template view
     * 
     */
    function index(Request $request)
    {
        return View::render('home');
    }
}
