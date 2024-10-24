<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','status','type'];

    public function user(){
        return $this->belongsTo(SpUser::class,'user_id','id');
    }
}
