<?php
namespace App\RequestHandler;

use Illuminate\Http\Request;

interface handleInterface
{

    public static function handle(Request $request);
}