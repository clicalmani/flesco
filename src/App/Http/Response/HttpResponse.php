<?php
namespace Clicalmani\Flesco\App\Http\Response;

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
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            10
        );
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