<?php
namespace Clicalmani\Flesco\Http\Response;

class HttpResponse 
{

    static function json($data, $success = null)
    {
        return json_encode(
            [
                'success' => isset($success) ? $success: isset($data),
                'data'    => $data
            ],
            JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK |
            JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR /** Enable strict mode */,
            512
        );
    }

    public function sendStatus(int $code)
    {
        return http_response_code($code);
    }

    static function success($message = '')
    {
        return self::json($message, true);
    }

    static function error($message = '')
    {
        return self::json($message, false);
    }
}