<?php

namespace App\Http\Controllers;

use App\Models\UserCopilotHistory;
use App\Services\UserCopilotHistoryService;
use Illuminate\Http\Request;

class UserCopilotHistoryController extends AuthenticatedController{
  
    public function index(){
        $histories = UserCopilotHistoryService::getUserHistories($this->authUser->id);
        return $this->successResponse([
            'histories' => $histories,
        ]);
    }

    public function show(Request $request , UserCopilotHistory $userCopilotHistory){
        $userCopilotHistory = UserCopilotHistoryService::getUserCopilotHistoryDetials( $this->authUser->id , $userCopilotHistory);
        return $this->successResponse([
            'history' => $userCopilotHistory,
        ]);
    }

    public function destroy(UserCopilotHistory $userCopilotHistory){
        UserCopilotHistoryService::deleteHistory( $this->authUser->id , $userCopilotHistory);

        return $this->successResponse([], 'History deleted');
    }

    public function download(UserCopilotHistory $history){
        $lastMessage = UserCopilotHistoryService::getDownloadableContent( $this->authUser->id, $history);
        return response()->json(
            $lastMessage->ai_response,
            200,
            [
                'Content-Disposition' => 'attachment; filename="history.json"',
            ]
        );
    }
}
