<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'complain_num',
        'brand_complaint_no',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'applicant_whatsapp',
        'extra_numbers',
        'reference_by',
        'applicant_adress',
        'description',
        'branch_id',
        'brand_id',
        'product',
        'model',
        'serial_number_ind',
        'serial_number_oud',
        'mq_nmb',
        'p_date',
        'complete_date',
        'amount',
        'product_type',
        'technician',
        'status',
        'working_details',
        'complaint_type',
        'provided_services',
        'warranty_type',
        'happy_call_remarks',
        'call_status',
        'files',
    ];

    public function user()
    {
        return $this->belongsTo(CrmUser::class);
    }

    public function brand()
    {
        return $this->belongsTo(AuthorizedBrands::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branches::class);
    }

}
