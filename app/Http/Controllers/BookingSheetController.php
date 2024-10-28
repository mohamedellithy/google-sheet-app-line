<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\GoogleSheetServices\FilterUpsertMessage;
use App\Services\GoogleSheetServices\GoogleSheetFilterService;

class BookingSheetController extends Controller
{

    public function auto_replay(Request $request,$user_id,$instance_id,$access_token){
        $data = $request->all();
        if($data['data']['event'] == 'messages.upsert'){
            foreach($data['data']['data']['messages'] as $message):
                if($message['key']['fromMe'] == false){
                    $body  = FilterUpsertMessage::FormateMessage($message);
                    $phone = intval($message['key']['remoteJid']);
                    $googel_sheet = new GoogleSheetFilterService($user_id,$phone,$instance_id,$access_token);
                    $googel_sheet->message = $body;

                    // incase bookings info reset
                    $googel_sheet->reset_booking_info();

                    // incase booking cancel
                    if($googel_sheet->cancel_booking_info() == true) return;

                    // start booking
                    $googel_sheet->handle();
                }
            endforeach;
        }
        return response()->json([
            'body'    => $data
        ]);
    }

}







