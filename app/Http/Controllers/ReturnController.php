<?php
namespace App\Http\Controllers;
use App\Order;
use App\Services\Commerce;
use App\Services\ReturnFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReturnController extends Controller {
 public function __construct(){$this->middleware(['auth',\App\Http\Middleware\ActiveAccount::class]);}
 public function store(Request $r,Order $order){abort_unless($order->user_id===$r->user()->id,403);$v=$r->validate(['key'=>'required|uuid','reason'=>'required|string|min:5|max:1000','quantities'=>'required|array|max:100','quantities.*'=>'required|integer|min:0|max:1000000']);ReturnFlow::request($order,$v);return back()->with('success','Return request submitted. Track it below.');}
 public function index(Request $r){abort_unless($r->user()->canManage('orders'),403);$v=$r->validate(['q'=>'nullable|string|max:100','status'=>'nullable|in:Requested,Approved,Received,Rejected']);$query=DB::table('order_returns')->join('orders','orders.id','=','order_returns.order_id')->select('order_returns.*','orders.number as order_number','orders.invoice_number','orders.email');if($r->filled('q')){$term='%'.$r->q.'%';$query->where(function($q)use($term){$q->where('order_returns.number','like',$term)->orWhere('orders.number','like',$term)->orWhere('orders.invoice_number','like',$term);});}if($r->filled('status'))$query->where('order_returns.status',$r->status);return view('admin.returns.index',['rows'=>$query->orderByDesc('order_returns.id')->paginate(20)->appends($r->query())]);}
 public function show(Request $r,$id){abort_unless($r->user()->canManage('orders'),403);$entry=DB::table('order_returns')->find($id);abort_unless($entry,404);$order=Order::findOrFail($entry->order_id);$lines=DB::table('order_return_items')->join('order_items','order_items.id','=','order_return_items.order_item_id')->where('return_id',$id)->select('order_return_items.*','order_items.name','order_items.sku','order_items.price')->get();return view('admin.returns.show',compact('entry','order','lines'));}
 public function action(Request $r,$id){abort_unless($r->user()->canManage('orders'),403);$v=$r->validate(['action'=>'required|in:approve,reject,receive,refund','notes'=>'nullable|string|max:1000','restock'=>'nullable|array|max:100','restock.*'=>'required|integer|min:0|max:1000000']);if($v['action']==='receive'){abort_unless($r->user()->canManage('inventory'),403);$r->validate(['notes'=>'required|string|min:5|max:1000','restock'=>'required|array']);}ReturnFlow::action($id,$v['action'],$v);return back()->with('success','Return updated.');}
}
