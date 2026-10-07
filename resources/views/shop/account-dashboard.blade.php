@extends('layouts.app')
@section('title','Your account')
@section('content')
<main class="account-dashboard"><div class="page-heading"><div><span class="eyebrow muted">YOUR ACCOUNT</span><h1>Hello, {{ auth()->user()->name }}</h1><p>Manage your orders, account details and shopping preferences.</p></div></div>
@if(!\App\Services\CustomerProfile::complete(auth()->user()))<div class="flash notice">Your profile is incomplete. <a class="text-link" href="/account/security#profile-details">Complete your profile and address</a> to start shopping.</div>@endif
<div class="account-tiles">
@foreach([
 ['orders','▣','Your Orders','Track orders, request returns or buy again','/orders'],
 ['security','♙','Login & Security','Edit your name, email, mobile number and password','/account/security'],
 ['payment','▤','Payment Options','Choose your preferred test payment method','/account/payments'],
 ['support','☏','Contact Us','Get help with your account or an order','/help'],
 ['address','⌖','Your Addresses','Add or edit your delivery addresses','/account/security#saved-addresses'],
 ['wishlist','♡','Your Wishlist','View the products you saved for later','/wishlist']
 ] as $tile)<a class="account-tile {{ $tile[0] }}" href="{{ $tile[4] }}"><span class="account-tile-icon" aria-hidden="true">{{ $tile[1] }}</span><div><h2>{{ $tile[2] }}</h2><p>{{ $tile[3] }}</p></div><span class="tile-arrow" aria-hidden="true">›</span></a>@endforeach
</div><div class="account-bottom"><a class="text-link" href="/shop">Continue shopping</a><a class="text-link" href="/notifications">Your notifications</a><form action="/logout" method="post">@csrf<button class="text-link">Sign out</button></form></div></main>
@endsection
