<?php
namespace Clicalmani\Flesco\Http\Client\Serializer;

use Clicalmani\Flesco\Http\Client\HttpRequest;
use Clicalmani\Flesco\Http\Client\Serializer;

/**
 * Class Json
 * @package PayPalHttp\Serializer
 *
 * Serializer for JSON content types.
 */
class Json implements Serializer
{

    public function contentType()
    {
        return "/^application\\/json/";
    }

    public function encode(HttpRequest $request)
    {
        $body = $request->body;
        if (is_string($body)) {
            return $body;
        }
        if (is_array($body)) {
            return json_encode($body);
        }
        throw new \Exception("Cannot serialize data. Unknown type");
    }

    public function decode($data)
    {
        return json_decode($data);
    }
}