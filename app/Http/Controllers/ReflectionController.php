<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ReflectionClass;

class ReflectionController extends Controller
{

    public function __construct() { }

    public function call_method_json(Request $request, $moduleName, $modelName, $id, $methodName) {
        $result = null;
        try {
            $ref = new ReflectionClass('Modules\\'.$moduleName.'\\Models\\'.$modelName);
            $row = $ref->getMethod('query')->invoke(null)->where("id", $id)->first();
            if($ref->hasMethod($methodName)) {
                $method = $ref->getMethod($methodName);
                $params = $method->getParameters();
                if(count($params) == 0) {
                    $result = $method->invoke($row);
                } else {
                    if($params[0]->getClass()->getName() == "Illuminate\Http\Request") {
                        $result = $method->invoke($row, $request);
                    } else {
                        $bodyParams = $request->get('params');
                        $result = $method->invokeArgs($row, $bodyParams);
                    }
                }
            }
        } catch (\Throwable $e) {
            return response()->json($e->getTraceAsString());
        }
        return response()->json($result);
    }
}
