<?php namespace App\Services\GoogleSheetServices;

class FilterUpsertMessage{
    public static function FormateMessage($message){
        if(isset($message['message']['conversation'])){
            return $message['message']['conversation'];
        } elseif(isset($message['message']['locationMessage'])){
            return self::location_message($message);
        } else {
            return isset($message['message']['extendedTextMessage']) ? $message['message']['extendedTextMessage']['text'] : null;
        }
    }

    public static function location_message($message){
        $message_text  = null;
        // $message_text .= "Lat :  ".$message['message']['locationMessage']['degreesLatitude'];
        // $message_text .= " | Long : ".$message['message']['locationMessage']['degreesLongitude'];
        $message_text .= "https://www.google.com/maps/@".$message['message']['locationMessage']['degreesLatitude'].",".$message['message']['locationMessage']['degreesLongitude'];
        return $message_text;
    }
}