<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Service\Copilot\GetPoints;
use Illuminate\Http\Request;

class RAGController extends Controller{
    
    public function search(Request $req){

        try{
            $question = $req->validated('question');
            $points = GetPoints::execute($)
        }
    }
}
