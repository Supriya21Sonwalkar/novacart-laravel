<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function dispatchTestOrder(\App\Order $order,$delivered=false)
    {
        $this->actingAs(\App\User::where('email','admin@novacart.test')->first());
        $this->post('/admin/outward',['request_key'=>(string)\Illuminate\Support\Str::uuid(),'type'=>'Order Dispatch','outward_date'=>today()->toDateString(),'source'=>'Main inventory','order_id'=>$order->id,'intent'=>'confirm'])->assertSessionHasNoErrors();
        $out=\App\Outward::where('active_order_id',$order->id)->firstOrFail();
        $this->post('/admin/outward/'.$out->id.'/action',['action'=>'pack'])->assertSessionHasNoErrors();
        $this->post('/admin/outward/'.$out->id.'/dispatch-details',['partner'=>'Test courier','tracking_number'=>'TRACK-'.$order->id])->assertSessionHasNoErrors();
        $this->post('/admin/outward/'.$out->id.'/action',['action'=>'dispatch'])->assertSessionHasNoErrors();
        if($delivered)$this->post('/admin/outward/'.$out->id.'/action',['action'=>'deliver','delivery_proof'=>'Confirmed received by test customer'])->assertSessionHasNoErrors();
        return $out;
    }
}
