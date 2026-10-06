<section class="checkout-section"><h3>Delivery availability</h3><p>{{ ucfirst($product->delivery_type) }}{{ $product->unit?' · '.$product->unit:'' }}{{ $product->cold_storage?' · Keep refrigerated':'' }}</p>
@if($product->expires_on)<p>Expiry date: {{ $product->expires_on }}</p>@endif
@if(session('delivery_pincode'))
@php($deliveryOptions=\App\Services\Delivery::options(collect([(object)['product'=>$product]]),session('delivery_pincode')))
@if(count($deliveryOptions))<p>Earliest available: <strong>{{ $deliveryOptions[0]['label'] }}</strong></p><p class="small">Based on pincode {{ session('delivery_pincode') }}. Confirmed at checkout.</p>@else<p>No delivery times currently available at {{ session('delivery_pincode') }}. Check another area or try later.</p>@endif
@else<p>Enter your delivery pincode above to check dates and express availability.</p>@endif</section>
