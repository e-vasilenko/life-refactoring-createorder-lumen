<?php declare(strict_types=1);

namespace App\Models;

use App\Services\OrderStatusService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'account',
        'payment_amount',
        'billing_amount',
        'commission_amount',
        'description',
        'payment_method',
        'customer',
    ];

    protected $casts = [
        'payment_amount' => 'float',
        'billing_amount' => 'float',
        'commission_amount' => 'float',
    ];

}
