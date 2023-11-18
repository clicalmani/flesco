<?php
namespace Clicalmani\Flesco\Http\Response;

use Clicalmani\Routes\Route;

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

        exit;
    }
}
