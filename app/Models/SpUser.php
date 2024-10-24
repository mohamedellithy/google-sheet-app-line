<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpUser extends Model
{
    use HasFactory;

    protected $table = "sp_users";

    public $timestamps = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    public function merchant_info(){
        return $this->hasMany(MerchantCredential::class,'user_id','id');
    }

    public function team(){
        return $this->hasOne(Team::class,'owner','id');
    }

    public function notifications(){
        return $this->hasMany(NotificationSubscriber::class,'user_id','id');
    }
}
