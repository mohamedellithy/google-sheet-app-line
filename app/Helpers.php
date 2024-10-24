<?php

use App\Models\Account;
use Illuminate\Support\Facades\Http;



if(!function_exists('send_message')):
    function send_message(
        $phone_number = null,$message      = null,
        $instance_id  = null,$access_token = null,$media = null){
        set_time_limit(0);
        $instance_id  = $instance_id  ?: '64AC6D08A99C9'; // '64B280D831EC1'
        $access_token = $access_token ?: '649ba622aa900'; // '64b2763270e61'

        if($phone_number == null) return 'failed';

        $phone_number = str_replace('+','',$phone_number);

        if($media != null):
            $end_point    = "https://wh.line.sa/api/send?number=$phone_number&type=type&media_url=$media&message=$message&instance_id=$instance_id&access_token=$access_token";
        elseif($media == null):
            $end_point    = "https://wh.line.sa/api/send?number=$phone_number&type=text&message=$message&instance_id=$instance_id&access_token=$access_token";
        endif;
        try{
            $client              = new \GuzzleHttp\Client();
            $send_result         = $client->post($end_point);
            $body                = $send_result->getBody()->getContents();
            $result_send_message = json_decode($body, true); // Decode as associative array
        } catch(Exception $e){
            $result_send_message['status'] = 'failed';
            \Log::info($e->getMessage());
        }

        // if($result_send_message['stats'] == false):
        //     send_message_error($result_send_message['type_erro'],$instance_id);
        // endif;
        return isset($result_send_message['status']) ? $result_send_message['status'] : 'failed';
    }
endif;

if(!function_exists('send_message_error')):
    function send_message_error($type_erro,$instance_owner_id){
        $instance_id  = "64AC6D08A99C9";
        $access_token = "649ba622aa900";
        set_time_limit(0);

        $account = Account::where('token',$instance_owner_id)->first();

        $phone_number_filter = explode('@',$account->pid);
        $phone_number        = $phone_number_filter[0];

        if($type_erro == "expiration_date"):
            $message = "عزيزي العميل\n
                        تم انتهاء باقتك في رسائل واتساب لاين.\n
                        يمكنك تجديد الباقة مباشرة من صفحة واتساب لاين\n
                        https://line.sa/19505\n";

        elseif($type_erro == "count_messages"):
            $message = "عزيزي العميل\n
                        تم استنفاد باقتك في رسائل واتساب لاين.\n
                        يمكنك تجديد الباقة مباشرة من صفحة واتساب لاين\n
                        https://line.sa/19505\n";

        endif;

        if($message):
            $end_point    = "https://wh.line.sa/api/send?number=$phone_number&type=text&message=$message&instance_id=$instance_id&access_token=$access_token";
        endif;
        $client   = new \GuzzleHttp\Client();
        $send_result         = $client->post($end_point);
        $body                = $send_result->getBody()->getContents();
        return [
            'result ' => $body
        ];
    }
endif;