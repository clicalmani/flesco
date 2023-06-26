<?php
namespace Clicalmani\Flesco\Http\Response;

Trait JsonResponse
{
    function sendStatus($status_code)
    {
        return http_response_code($status_code);
    }

    function json($data = null, $status = null)
    {
        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK |
            JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR /** Enable strict mode */,
            512
        );
    }

    function success($data = null)
    {
        return $this->json(['success' => true, 'data' => $data]);
    }

    function error($data = null)
    {
        return $this->json(['success' => false, 'data' => $data]);
    }

    function status($status_code, $code = null, $message = '')
    {
        $this->sendStatus($status_code);
        echo $this->json(['success' => false, 'error_code' => $code, 'error_message' => $message], $status_code);
    }
}