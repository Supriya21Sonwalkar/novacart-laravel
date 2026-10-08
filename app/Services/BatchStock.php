<?php
namespace App\Services;
use App\Product;
use Illuminate\Support\Facades\DB;
class BatchStock {
 public static function available(Product $p){$q=DB::table('product_batches')->where('product_id',$p->id);if(!$q->exists())return $p->expires_on&&$p->expires_on<today()->toDateString()?0:(int)$p->stock;return (int)$q->where(function($q){$q->whereNull('expires_on')->orWhere('expires_on','>=',today()->toDateString());})->sum('remaining_quantity');}
 // Call only inside a transaction, after locking the product row.
 public static function opening(Product $p){if(!DB::table('product_batches')->where('product_id',$p->id)->exists()&&$p->stock>0)self::receive($p,(int)$p->stock,['batch_number'=>'OPEN-'.$p->id,'received_at'=>$p->created_at?:now(),'expires_on'=>$p->expires_on,'source'=>'Opening balance'],false);}
 public static function receive(Product $p,$quantity,array $data,$increase=true){
  if($quantity<1||$quantity>1000000)Commerce::error('Received quantity must be between 1 and 1000000.');
  if($increase)self::opening($p);
  $number=trim($data['batch_number']);if($number===''||strlen($number)>80)Commerce::error('A batch number is required.');
  $received=\Carbon\Carbon::parse($data['received_at']);if($received->gt(now()))Commerce::error('Stock cannot be received in the future.');
  $expiry=$data['expires_on']??null;
  if($increase&&in_array($p->delivery_type,['dairy','fresh'],true)&&!$expiry)Commerce::error('An expiry date is required for perishable stock.');$manufactured=$data['manufactured_on']??null;
  if($manufactured&&$manufactured>$received->toDateString())Commerce::error('Manufacturing date must not be after receipt.');
  if($increase&&$expiry&&($expiry<$received->toDateString()||($manufactured&&$expiry<$manufactured)))Commerce::error('Expiry must not be before receipt or manufacture.');
  if(DB::table('product_batches')->where('product_id',$p->id)->where('batch_number',$number)->exists())Commerce::error('Batch '.$number.' already exists for this product. Use its original receipt; do not duplicate it.');
  $id=DB::table('product_batches')->insertGetId(['product_id'=>$p->id,'inward_item_id'=>$data['inward_item_id']??null,'batch_number'=>$number,'supplier_invoice_no'=>$data['supplier_invoice_no']??null,'received_at'=>$received,'manufactured_on'=>$manufactured,'expires_on'=>$expiry,'received_quantity'=>$quantity,'remaining_quantity'=>$quantity,'unit_cost'=>$data['unit_cost']??null,'source'=>$data['source']??'Inward','created_at'=>now(),'updated_at'=>now()]);
  if($increase){$p->increment('stock',$quantity);}return $id;
 }
 public static function consume(Product $p,$quantity,array $reference=[],$mode='usable'){
  if($quantity<1)Commerce::error('Issued quantity must be positive.');self::opening($p);$q=DB::table('product_batches')->where('product_id',$p->id)->where('remaining_quantity','>',0);
  if($mode==='usable')$q->where(function($q){$q->whereNull('expires_on')->orWhere('expires_on','>=',today()->toDateString());});
  if($mode==='expired')$q->whereNotNull('expires_on')->where('expires_on','<',today()->toDateString());
  $batches=$q->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
  if($batches->sum('remaining_quantity')<$quantity||$p->stock<$quantity)Commerce::error('Insufficient '.$mode.' batch stock for '.$p->name.'.');
  $left=$quantity;foreach($batches as $b){if(!$left)break;$take=min($left,$b->remaining_quantity);DB::table('product_batches')->where('id',$b->id)->decrement('remaining_quantity',$take);DB::table('batch_allocations')->insert(['batch_id'=>$b->id,'order_item_id'=>$reference['order_item_id']??null,'outward_item_id'=>$reference['outward_item_id']??null,'quantity'=>$take,'unit_cost'=>$b->unit_cost,'created_at'=>now(),'updated_at'=>now()]);$left-=$take;}
  $p->decrement('stock',$quantity);
 }
 public static function adjust(Product $p,$target){self::opening($p);$delta=$target-$p->stock;if($delta>0)self::receive($p,$delta,['batch_number'=>'ADJ-'.strtoupper(\Illuminate\Support\Str::random(12)),'received_at'=>now(),'expires_on'=>$p->expires_on,'source'=>'Admin adjustment']);elseif($delta<0)self::consume($p,-$delta,[],'all');}
 public static function allocations($itemId){return DB::table('batch_allocations')->where('order_item_id',$itemId)->orderBy('id')->lockForUpdate()->get();}
 public static function legacy($item){
  if(DB::table('batch_allocations')->where('order_item_id',$item->id)->exists())return;
  $p=Product::withTrashed()->where('id',$item->product_id)->lockForUpdate()->first();if(!$p)Commerce::error('The original product is missing.');self::opening($p);
  $number='LEGACY-ORDER-'.$item->id;$id=DB::table('product_batches')->insertGetId(['product_id'=>$p->id,'batch_number'=>$number,'received_at'=>now(),'expires_on'=>$p->expires_on,'received_quantity'=>$item->quantity,'remaining_quantity'=>0,'source'=>'Legacy order allocation','created_at'=>now(),'updated_at'=>now()]);
  DB::table('batch_allocations')->insert(['batch_id'=>$id,'order_item_id'=>$item->id,'quantity'=>$item->quantity,'created_at'=>now(),'updated_at'=>now()]);
 }
 public static function restore($item,$quantity,$restock,$reason){
  $p=Product::withTrashed()->where('id',$item->product_id)->lockForUpdate()->firstOrFail();self::legacy($item);$alloc=self::allocations($item->id);
  if($quantity<1||$restock<0||$restock>$quantity||$alloc->sum(function($a){return $a->quantity-$a->returned_quantity;})<$quantity)Commerce::error('Return quantity exceeds the original unreturned quantity.');
  $left=$quantity;$usable=$restock;foreach($alloc as $a){if(!$left)break;$take=min($left,$a->quantity-$a->returned_quantity);if(!$take)continue;$b=DB::table('product_batches')->where('id',$a->batch_id)->lockForUpdate()->first();$restore=min($usable,$take);if($restore&&$b->expires_on&&$b->expires_on<today()->toDateString())Commerce::error('Batch '.$b->batch_number.' has expired. Record it as unusable instead of restocking.');if($restore)DB::table('product_batches')->where('id',$b->id)->increment('remaining_quantity',$restore);DB::table('batch_allocations')->where('id',$a->id)->update(['returned_quantity'=>$a->returned_quantity+$take,'restored_quantity'=>$a->restored_quantity+$restore,'updated_at'=>now()]);$left-=$take;$usable-=$restore;}
  if($restock){$p->increment('stock',$restock);DB::table('inventory')->insert(['product_id'=>$p->id,'adjustment'=>$restock,'reason'=>$reason,'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);}
 }
 public static function cancel($order){foreach($order->items->sortBy('product_id') as $item){self::legacy($item);$quantity=self::allocations($item->id)->sum(function($a){return $a->quantity-$a->returned_quantity;});if($quantity)self::restoreCancelled($item,$quantity,$order->number);}}
 private static function restoreCancelled($item,$quantity,$number){
  // Cancellations put reserved goods back in their original batch, even if now expired.
  $p=Product::withTrashed()->where('id',$item->product_id)->lockForUpdate()->firstOrFail();foreach(self::allocations($item->id) as $a){$qty=$a->quantity-$a->returned_quantity;if(!$qty)continue;DB::table('product_batches')->where('id',$a->batch_id)->increment('remaining_quantity',$qty);DB::table('batch_allocations')->where('id',$a->id)->update(['returned_quantity'=>$a->quantity,'restored_quantity'=>$a->restored_quantity+$qty,'updated_at'=>now()]);}$p->increment('stock',$quantity);DB::table('inventory')->insert(['product_id'=>$p->id,'adjustment'=>$quantity,'reason'=>'Cancelled '.$number,'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
 }
 public static function dispatchCheck($order,$date){foreach($order->items->sortBy('product_id') as $item){self::legacy($item);foreach(self::allocations($item->id) as $a){$b=DB::table('product_batches')->where('id',$a->batch_id)->first();if($b->expires_on&&$b->expires_on<$date)Commerce::error('Allocated batch '.$b->batch_number.' expires before delivery. Cancel and recreate the order with fresh stock.');}}}
}
