<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSubCategory extends Model
{
    use HasFactory;

    protected $table = 'expense_subcategories';

    protected $fillable = [
        'category_name',
        'name'
    ];
}
