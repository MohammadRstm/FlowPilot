<?php

namespace App\Http\Controllers;

use App\Models\UserCopilotHistory;
use App\Models\Message;
use App\Http\Controllers\Controller;
use App\Service\UserCopilotHistoryService;
use App\Service\UserService;
use Illuminate\Http\Request;

class UserCopilotHistoryController extends Controller{
  
    public function index(Request $request){
        $userId = $request->user()->id; 
        $histories = UserCopilotHistoryService::getUserHistories($userId);
        return $this->successResponse([
            'histories' => $histories,
        ]);
    }

    public function show(Request $request , UserCopilotHistory $userCopilotHistory){
        $userId = $request->user()->id;  

        $userCopilotHistory = UserCopilotHistoryService::getUserCopilotHistoryDetials($userId , $userCopilotHistory);

        return $this->successResponse([
            'history' => $userCopilotHistory,
        ]);
    }

    public function destroy(Request $request , UserCopilotHistory $userCopilotHistory){
        $userId = $request->user()->id; 
        UserCopilotHistoryService::deleteHistory($userId , $userCopilotHistory);

        return $this->successResponse([], 'History deleted');
    }

    public function download(Request $request , UserCopilotHistory $history){
        $userId = $request->user()->id;
        $lastMessage = UserCopilotHistoryService::getDownloadableContent($userId, $history);
        return response()->json(
            $lastMessage->ai_response,
            200,
            [
                'Content-Disposition' => 'attachment; filename="history.json"',
            ]
        );
    }
}
