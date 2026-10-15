<?php
namespace App\Http\Controllers;
use App\Order;
use App\Outward;
use App\User;
use App\StoreRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OrderWorkspaceController extends Controller {
 public function __construct(){$this->middleware(['auth',\App\Http\Middleware\ActiveAccount::class]);$this->middleware(function($r,$next){abort_unless($r->user()->canManage('orders'),403);return $next($r);});}
 public function show(Order $order){
  $entry=Outward::with('items','order')->where('active_order_id',$order->id)->first();if(!$entry&&!in_array($order->status,['Placed','Confirmed','Packing','Packed'],true))$entry=Outward::with('items','order')->where('order_id',$order->id)->latest()->first();
  return view('admin.order-workspace',['order'=>$order,'entry'=>$entry,'payment'=>DB::table('payments')->where('order_id',$order->id)->first(),'allocations'=>DB::table('batch_allocations')->join('order_items','order_items.id','=','batch_allocations.order_item_id')->join('product_batches','product_batches.id','=','batch_allocations.batch_id')->where('order_items.order_id',$order->id)->select('batch_allocations.*','product_batches.batch_number','product_batches.supplier_invoice_no','product_batches.expires_on','order_items.name')->get(),'returns'=>DB::table('order_returns')->where('order_id',$order->id)->get(),'delivery'=>DB::table('deliveries')->where('order_id',$order->id)->first(),'riders'=>User::where('active',true)->whereIn('role_id',StoreRecord::in('roles')->where('name','delivery partner')->pluck('id'))->get()]);
 }
 public function prepare(Request $r,Order $order){abort_unless($r->user()->canManage('inventory'),403);\App\Services\OrderLifecycle::requirePayment($order);$v=$r->validate(['request_key'=>'required|uuid']);$request=Request::create('/admin/outward','POST',$v+['type'=>'Order Dispatch','outward_date'=>today()->toDateString(),'source'=>'Main inventory','order_id'=>$order->id,'intent'=>'confirm']);$request->setUserResolver(function()use($r){return $r->user();});(new OutwardController)->store($request);return redirect()->route('admin.order-workspace',$order)->with('success','Order outward prepared. Review allocated batches, then pack and dispatch below.');}
}
