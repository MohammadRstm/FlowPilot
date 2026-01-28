<?php

namespace App\Http\Controllers;
use App\Traits\JsonResponseTrait;
use Illuminate\Routing\Controller as BaseController;


abstract class Controller extends BaseController{
    use JsonResponseTrait;
}
