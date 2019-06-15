<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\RequestHandler\generateRequest;


class requestController extends Controller
{


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct( )
    {

    }







    public function execute(Request $request)
    {
        generateRequest::handle( $request);
    }




    public function getHttp(Request $request)
    {
        $this->execute($request);
        return  $request;
    }


}
