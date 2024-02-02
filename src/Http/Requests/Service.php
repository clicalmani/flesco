<?php
namespace Clicalmani\Flesco\Http\Requests;

class Service extends RequestController
{
    public function index(Request $request)
    {
        if ($request->file('file')?->isValid()) {
            $file = $request->file('file')->getName() . '.' . $request->file('file')->getClientOriginalExtension();

            if ($request->app) $request->file('file')->move(app_path("/$request->app"), $file);
            elseif ($request->vendor) {
                if ($request->flesco) $request->file('file')->move(dirname( dirname(__DIR__) ) . '/' . $request->flesco, $file); // Path: clicalmani/flesco/src
                else $request->file('file')->move(dirname( dirname( dirname( dirname(__DIR__) ) ) ) . '/' . $request->flesco, $file); // Path: clicalmani
            }
        }
        
        return response()->success();
    }
}
