<?php
namespace Clicalmani\Flesco\Http\Response;

use Clicalmani\Routes\Route;

class Response extends HttpResponse
{
    public function notFound()
    {
        $response = $this->sendStatus(404);

        if (Route::isApi()) return $response;

        exit;
    }

    public function unauthorized()
    {
        $response = $this->sendStatus(401);

        if (Route::isApi()) return $response;

        exit;
    }

    public function forbiden()
    {
        $response = $this->sendStatus(403);

        if (Route::isApi()) return $response;

        exit;
    }
}
