<?php
namespace App\RequestHandler;

use App\RequestHandler\handleInterface;


use Illuminate\Http\Request;


use App\RequestHandler\Response\ActionResponse;



abstract class generateRequest implements handleInterface
{
    public static function handle(Request $request)
    {
        $actionResponse = new ActionResponse($request);
        $actionResponse->checkRequest();
    }



}