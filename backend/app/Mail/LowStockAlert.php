<?php

namespace App\Mail;

use App\Models\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $inventory;
    public $threshold;

    /**
     * Create a new message instance.
     */
    public function __construct(Inventory $inventory, $threshold = 50)
    {
        $this->inventory = $inventory;
        $this->threshold = $threshold;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Low Stock Alert: ' . $this->inventory->seedling_type)
                    ->view('emails.low-stock-alert')
                    ->with([
                        'seedlingType' => $this->inventory->seedling_type,
                        'classification' => $this->inventory->classification,
                        'currentQuantity' => $this->inventory->total_quantity,
                        'threshold' => $this->threshold,
                        'location' => $this->inventory->location ?? 'Not specified',
                    ]);
    }
}
