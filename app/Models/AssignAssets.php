<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignAssets extends Model
{
    use HasFactory;
    
      protected $fillable = [
        'employee_id',
        'categories',
        'model_number',
        'status',
        'itemname',
        'model',
        'serial_number',
        'assigned_by',
        'item_remark',
        'assigned_date',
        'description',
        'image',
        
    ];
}
