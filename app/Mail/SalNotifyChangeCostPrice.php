<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SalNotifyChangeCostPrice extends Mailable
{
    use Queueable, SerializesModels;
    public $EffectFrom;
    public $UserName;
    public $Sku;
    public $ProductName;
    public $OldCost;
    public $NewCost;
    public $OldPrice;
    public $NewPrice;
    public $ChannelName;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( $EffectFrom, $UserName,$Sku, $ProductName,$OldCost,$NewCost,$OldPrice,$NewPrice,$ChannelName)
    {
        $this->EffectFrom = $EffectFrom;
        $this->UserName = $UserName;
        $this->Sku = $Sku;
        $this->ProductName = $ProductName;
        $this->OldCost = $OldCost;
        $this->NewCost = $NewCost;
        $this->OldPrice = $OldPrice;
        $this->NewPrice = $NewPrice;
        $this->ChannelName = $ChannelName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('Emails.Sales.ChangeCostPrice');
       
    }
}
