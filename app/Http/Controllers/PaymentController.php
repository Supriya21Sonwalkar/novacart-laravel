<?php
namespace App\Http\Controllers;
use App\Order;
use App\Services\Commerce;
use App\Services\PaymentFlow;
use Illuminate\Http\Request;
class PaymentController extends Controller {
 public function __construct(){
        try {
            $this->middleware(['auth', \App\Http\Middleware\ActiveAccount::class]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
 public function sandbox(Request $r,Order $order){
        try {
            abort_unless(PaymentFlow::enabled(), 404);
            abort_unless(Commerce::owned(Order::where('id', $order->id))->exists(), 403);
            $v = $r->validate(['outcome' => 'required|in:Paid,Failed']);
            PaymentFlow::settle($order, $v['outcome']);
            return back()->with('success', 'Sandbox payment outcome recorded. No money moved.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}
