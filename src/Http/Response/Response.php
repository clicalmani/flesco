<?php
namespace Clicalmani\Flesco\Http\Response;

use Clicalmani\Flesco\Routing\Route;

/**
 * Class Response
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
class Response extends HttpResponse
{
    /**
     * 404 Not found redirect
     * 
     * @return mixed
     */
    public function notFound() : mixed
    {
        $response = $this->sendStatus(404);

        if (Route::isApi()) return $response;

        /**
         * |------------------------------------------------------------
         * |                     Default 404
         * |------------------------------------------------------------
         */

         echo view('404');
        exit;
    }

    /**
     * 401 Unauthorized redirect
     * 
     * @return mixed
     */
    public function unauthorized() : mixed
    {
        $response = $this->sendStatus(401);

        if (Route::isApi()) return $response;
        
        /**
         * |------------------------------------------------------------
         * |                     Default 401
         * |------------------------------------------------------------
         */

        echo view('401');
        exit;
    }

    /**
     * 403 Forbiden redirect
     * 
     * @return mixed
     */
    public function forbiden() : mixed
    {
        $response = $this->sendStatus(403);

        if (Route::isApi()) return $response;

        /**
         * |------------------------------------------------------------
         * |                     Default 403
         * |------------------------------------------------------------
         */

         echo view('403');
        exit;
    }

    /**
     * 403 Forbiden redirect
     * 
     * @return mixed
     */
    public function internalServerError() : mixed
    {
        $response = $this->sendStatus(500);

        if (Route::isApi()) return $response;

        /**
         * |------------------------------------------------------------
         * |                     Default 500
         * |------------------------------------------------------------
         */

         echo view('500');
        exit;
    }
}
