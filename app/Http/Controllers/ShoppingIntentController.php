<?php
namespace App\Http\Controllers;
use App\Product;
use App\Services\Commerce;
use App\Services\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ShoppingIntentController extends Controller {
 public function remember(Request $r){$v=$r->validate(['product_id'=>'required|integer|exists:products,id','kind'=>'required|in:cart,wishlist','quantity'=>'nullable|integer|between:1,999','variant'=>'nullable|string|max:120']);session(['shopping_intent'=>$v+['expires'=>time()+1800]]);return response()->json(['saved'=>true]);}
 private function intent(){ $intent=session('shopping_intent');if(!$intent||$intent['expires']<time()){session()->forget('shopping_intent');return null;}return $intent;}
 public function resume(){if(!auth()->check())return redirect()->guest('/login');if(!CustomerProfile::complete(auth()->user()))return redirect('/account')->with('notice','Complete your profile, then confirm the product you selected.');$intent=$this->intent();if(!$intent)return redirect('/shop');$product=Product::where('active',true)->find($intent['product_id']);if(!$product){session()->forget('shopping_intent');return redirect('/shop')->with('notice','The selected product is no longer available.');}return view('shop.resume',compact('intent','product'));}
 public function confirm(){abort_unless(auth()->check(),401);CustomerProfile::requireComplete();$intent=$this->intent();if(!$intent)return redirect('/shop');$product=Product::where('active',true)->findOrFail($intent['product_id']);if($intent['kind']==='cart')Commerce::add($product,$intent['quantity']??1,$intent['variant']??$product->options[0]);else{DB::transaction(function()use($product){$w=DB::table('wishlists')->where('user_id',auth()->id())->first();$id=$w?$w->id:DB::table('wishlists')->insertGetId(['user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);DB::table('wishlist_items')->updateOrInsert(['wishlist_id'=>$id,'product_id'=>$product->id],['created_at'=>now(),'updated_at'=>now()]);});}session()->forget('shopping_intent');return redirect($intent['kind']==='cart'?'/cart':'/wishlist')->with('success','Your selected product was added.');}
}
