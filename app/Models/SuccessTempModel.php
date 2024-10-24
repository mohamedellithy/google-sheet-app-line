<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuccessTempModel extends Model
{
    use HasFactory;
    
    protected  $table ="sp_success_temp";

    public $timestamps = false;
    
    const CREATED_AT = null;
    const UPDATED_AT = null;
}
