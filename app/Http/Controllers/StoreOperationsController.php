<?php
namespace App\Http\Controllers;
use App\Services\StoreReporting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StoreOperationsController extends Controller {
 public function __construct(){$this->middleware(['auth',\App\Http\Middleware\ActiveAccount::class]);$this->middleware(function($r,$next){abort_unless($r->user()->canManage('reports'),403);return $next($r);});}
 public function report(Request $r){$v=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from']);$from=$v['from']??today()->startOfMonth()->toDateString();$to=$v['to']??today()->toDateString();if($from>$to)\App\Services\Commerce::error('The end date must follow the start date.');return view('admin.store-operations',['from'=>$from,'to'=>$to,'figures'=>StoreReporting::calculate($from,$to),'expenses'=>DB::table('store_expenses')->whereBetween('spent_on',[$from,$to])->latest()->get(),'outbox'=>DB::table('store_mail_outbox')->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total','status')]);}
 public function expense(Request $r){$v=$r->validate(['spent_on'=>'required|date|before_or_equal:today','description'=>'required|string|max:250','amount'=>'required|numeric|min:0.01|max:10000000']);DB::table('store_expenses')->insert(['spent_on'=>$v['spent_on'],'description'=>$v['description'],'amount'=>(int)round($v['amount']*100),'created_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);\App\Services\Commerce::log('Operating expense recorded',['description'=>$v['description']]);return back()->with('success','Expense recorded.');}
}
