<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerInvoice extends Model
{
    /**
     * setup variable mass assignment.
     *
     * @var array
     */

    protected $table = 'customer_invoices';
    protected $primaryKey = 'id';

}