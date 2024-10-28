<?php namespace App\Services\GoogleSheetServices;

use App\Models\GoogleSheetAutoReplay;
use App\Services\GoogleSheetServices\AccountService;
use App\Services\GoogleSheetServices\GoogleSheetOperation;
use Carbon\Carbon;
class GoogleSheetFilterService extends GoogleSheetOperation {
    use AccountService;
    public $booking_sheet_words = [];
    public $booking_appointments = [];
    public $message = null;
    public $values_sheet = null;
    public $google_sheet;
    public function __construct(public $user_id,public $phone,public $instance_id,public $access_token){
        parent::__construct();
        $this->google_sheet = GoogleSheetAutoReplay::where([
            'user_id' => $this->user_id,
            'phone'   => $this->phone
        ])->first();

        $this->booking_sheet_words  = $this->booking_sheet_words();
        $this->booking_appointments = $this->get_appointments();

        $this->values_sheet = $this->google_sheet?->value ? json_decode($this->google_sheet?->value,true): [];
    }

    public function appointments(){
        $prev_values_container = [];
        if(!isset($this->google_sheet->next_appointment)){
            $this->google_sheet->update([
                'next_appointment'    => 'date',
            ]);
        } elseif($this->google_sheet->next_appointment == 'date'){
            if(isset($this->booking_appointments[$this->message][0])){
                $this->save_data($this->google_sheet->next_appointment,$this->booking_appointments[$this->message][0]);
                $this->google_sheet->update([
                    'next_appointment'    => 'day'
                ]);
            }
        }
        elseif($this->google_sheet->next_appointment == 'day'){
            if(isset($this->booking_appointments[$this->message][1])){
                $this->save_data($this->google_sheet->next_appointment,$this->booking_appointments[$this->message][1]);
                $this->google_sheet->update([
                    'next_appointment'    => 'times'
                ]);
            }
        }
        elseif($this->google_sheet->next_appointment == 'times'){
            $question_validate_replay = 0;
            foreach($this->booking_appointments as $key => $booking_times):
                if($booking_times[0] == $this->values_sheet['date']){
                    if(isset($booking_times[$this->message])){
                        if($this->validate_count_appointments()){
                            $question_validate_replay = 1;
                            $this->save_data($this->google_sheet->next_appointment,$booking_times[$this->message]);
                        }
                        break;
                    }
                }
            endforeach;

            if($question_validate_replay == 1){
                $this->google_sheet->update([
                    'next_appointment'    => 'end'
                ]);

                // reback to all section
                $this->next_question();
                $this->send_message($this->google_sheet->current_question);
            }
        }

        $need_message = null;
        if($this->google_sheet->next_appointment == 'date'){
            $need_message  = "حدد اليوم المناسب لك \n\n";
            $need_message .= "قم بالرد بكتابة رقم اليوم المحدد \n\n";
            foreach($this->booking_appointments as $key => $booking_appointment):
                if(!in_array($booking_appointment[0],$prev_values_container)){
                    $prev_values_container[] = $booking_appointment[0];
                    $need_message .= '#'.$key.' => '.$booking_appointment[0]."\n";
                }
            endforeach;
        } elseif($this->google_sheet->next_appointment == 'day'){
            $need_message = "حدد التاريخ المناسب لك \n\n";
            $need_message .= "قم بالرد بكتابة رقم التاريخ المحدد \n\n";
            if(isset($this->booking_appointments[$this->message][0])):
                $have_valid_date = [];
                foreach($this->booking_appointments as $key => $booking_day):
                    if($booking_day[0] == $this->booking_appointments[$this->message][0]){
                        $Max_Date = strtotime('+30 days');
                        $Min_Date = strtotime("+1 days");
                        $handle_date = strtotime($booking_day[1]);
                        if(!in_array($booking_day[1],$prev_values_container)){
                            $prev_values_container[] = $booking_day[1];
                            if(($Max_Date >= $handle_date)){
                                if(($Min_Date <= $handle_date)){
                                    $have_valid_date[] = $booking_day[1];
                                    $need_message .= '#'.$key.' => '.$booking_day[1]."\n";
                                }
                            }
                        }
                    }
                endforeach;

                if(count($have_valid_date) == 0):
                    $this->google_sheet->update([
                        'next_appointment'    => 'date'
                    ]);
                    $this->novalid_massage();
                    return;
                endif;
            else:
                $this->reback_massage();
                return;
            endif;
        } elseif($this->google_sheet->next_appointment == 'times'){
            $need_message = "حدد الوقت المناسب لك \n\n";
            $need_message .= "قم بالرد بكتابة رقم الوقت المحدد \n\n";
            if(isset($this->booking_appointments[$this->message][0])):
                foreach($this->booking_appointments as $key => $booking_times):
                    if($booking_times[1] == $this->booking_appointments[$this->message][1]):
                        foreach($booking_times as $index => $item):
                            if(!in_array($index,[0,1])):
                                if(isset($item)){
                                    if(!in_array($item,$prev_values_container)){
                                        $prev_values_container[] = $item;
                                        $need_message .= '#'.$index.' => '.$item."\n";
                                    }
                                }
                            endif;
                        endforeach;
                    endif;
                endforeach;
            else:
                $this->reback_massage();
                return;
            endif;
        }

        $this->send_message(urlencode($need_message));
    }

    public function save_data($name,$value){
        $this->values_sheet[$name] = trim($value);
        $this->google_sheet->update([
            'value' => $this->values_sheet
        ]);
    }

    public function validate_count_appointments(){
        $count = GoogleSheetAutoReplay::whereJsonContains('value->date',$this->values_sheet['date'])
        ->whereJsonContains('value->day',$this->values_sheet['day'])
        ->whereJsonContains('value->times',$this->values_sheet['times'])->count();

        if($count > 3){
            $this->novalid_massage();
            return false;
        }

        return true;
    }

    public function handle(){
        if(!$this->google_sheet){
            $this->google_sheet = GoogleSheetAutoReplay::create([
                'user_id' => $this->user_id,
                'phone'   => $this->phone,
                'current_question' => null,
                'next_question' => null
            ]);
        }

        if(isset($this->google_sheet->current_question)){
            if($this->google_sheet->current_question != 'موعد الغسيل'){
                $this->save_data($this->google_sheet->current_question,$this->message);
            }
        }

        if(($this->google_sheet->next_question =='end') && ($this->google_sheet->next_appointment =='end')){
            if($this->google_sheet->current_question != 'end'){
                $this->end_message();
            }
            return;
        }

        if(!isset($this->google_sheet->current_question)){
            $this->google_sheet->update([
                'current_question' => $this->booking_sheet_words[0][0],
                'next_question'    => 1,
            ]);
        } elseif(isset($this->google_sheet->current_question)){
            if(($this->google_sheet->current_question != 'موعد الغسيل') || ($this->google_sheet->next_appointment == 'end')){
                $this->next_question();
            }
        }

        if(($this->google_sheet->current_question == 'موعد الغسيل') && ($this->google_sheet->next_appointment != 'end')){
            $this->appointments();
        } else {
            $this->send_message($this->google_sheet->current_question);
        }
    }
    
    public function next_question(){
        if($this->google_sheet?->next_question == 'end'){
            $this->end_message();
        } else {
            $next_index = $this->google_sheet->next_question + 1;
            $check_if_have_question = isset($this->booking_sheet_words[0][$next_index]) ? $next_index: 'end';
            $current_question = isset($this->booking_sheet_words[0][$this->google_sheet->next_question])
            ? $this->booking_sheet_words[0][$this->google_sheet->next_question] : $this->google_sheet->current_question;
            $this->google_sheet->update([
                'current_question' => $current_question,
                'next_question'    => $check_if_have_question,
            ]);
        }
    }

    public function end_message(){
        $this->insert_new_row(
            $this->google_sheet->id,
            $this->values_sheet
        );

        $this->google_sheet->update([
            'current_question' => 'end'
        ]);

        $message  = "شكرا لاختيارك خدماتنا";
        $message .= "رقم الحجز الخاص بك ". $this->google_sheet?->id."\n";
        $message .= "فى حال رغبتك اعادة جدولة الحجز ارسل "."001"."\n";
        $message .= "فى حال رغبتك الغاء الحجز ارسل "."002"."\n";
        $this->send_message($message);
    }

    public function reback_massage(){
        $message = "الرقم الذي قمت بادخاله غير صحيح من فضلك قم بالاختيار من ضمن القائمة\n";
        $this->send_message($message);
    }

    public function novalid_massage(){
        $message = "عذرا الاختيار غير متاح حاليا الرجاء اختيار خيار أخر";
        $this->send_message($message);
    }

    public function reset_booking_info(){
        if($this->message === '001'){
            if($this->google_sheet){
                $this->delete_selected_row($this->google_sheet?->id);
                $this->google_sheet->delete();
                $this->google_sheet = null;
            }
        }
    }

    public function cancel_booking_info(){
        if($this->message === '002'){
            if($this->google_sheet){
                $this->delete_selected_row($this->google_sheet?->id);
                $this->google_sheet->delete();
                $this->google_sheet = null;

                $message = "تم الغاء الحجز الخاص بك بنجاح";
                $this->send_message($message);
            }

            return true;
        }

        return false;
    }

    public function send_message($message){
        if($message == 'end') return;
        send_message(
            $this->phone,
            $message,
            $this->instance_id,
            $this->access_token
        );
    }
}