<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ApiResponseHelper
{
    public function successResponse($data,$message = "", $code = Response::HTTP_OK){
        return response()->json(['data' => $data,'msg' => $message], $code);
    }

    public function errorResponse($message, $code){
        return response()->json(['error' => $message, 'code'=>$code], $code);
    }
}
