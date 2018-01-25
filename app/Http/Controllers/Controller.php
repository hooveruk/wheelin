<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Wrapper for success response
     * @param $data
     */
    public function responseSuccess($data)
    {
        return ['status' => 'ok', 'data' => $data];
    }

    /**
     * Wrapper for error response
     * @param $error    integer
     * @param $errorMsg string
     * @param $data     array
     */
    public function responseError($error, $errorMsg, $data = [])
    {
        return ['status' => 'error', 'error_id' => $error, 'message' => $errorMsg, 'data' => $data];
    }

}
