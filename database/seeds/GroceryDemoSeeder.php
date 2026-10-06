<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\StoreRecord;
use App\User;
class GroceryDemoSeeder extends Seeder {
 public function run(){
  $root=StoreRecord::in('categories')->firstOrCreate(['name'=>'Grocery'],['data'=>['position'=>0]]);
  $dairy=StoreRecord::in('categories')->firstOrCreate(['name'=>'Dairy'],['data'=>['parent_id'=>$root->id,'position'=>1]]);
  $fresh=StoreRecord::in('categories')->firstOrCreate(['name'=>'Fruits & Vegetables'],['data'=>['parent_id'=>$root->id,'position'=>2]]);
  $snacks=StoreRecord::in('categories')->firstOrCreate(['name'=>'Packaged Food'],['data'=>['parent_id'=>$root->id,'position'=>3]]);
  $brand=StoreRecord::in('brands')->firstOrCreate(['name'=>'Nova Fresh'],['data'=>[]]);
  foreach([
   ['NC-MILK-001','Fresh Milk',6500,$dairy->id,'dairy','1 litre','milk,dudh,दूध,dairy','/grocery-milk.svg'],
   ['NC-CURD-001','Fresh Curd',4500,$dairy->id,'dairy','500 g','curd,dahi,दही,dairy','/grocery-curd.svg'],
   ['NC-APPLE-001','Fresh Apples',18000,$fresh->id,'fresh','1 kg','apple,fruit,फळ','/grocery-apple.svg'],
   ['NC-RICE-001','Everyday Rice',9500,$snacks->id,'grocery','1 kg','rice,chawal,तांदूळ,groceries','/grocery-rice.svg']
  ] as $row)Product::firstOrCreate(['sku'=>$row[0]],['name'=>$row[1],'price'=>$row[2],'stock'=>30,'category_id'=>$row[3],'brand_id'=>$brand->id,'delivery_type'=>$row[4],'unit'=>$row[5],'tags'=>$row[6],'image'=>$row[7],'variants'=>'Default','description'=>'Sample grocery product for testing delivery. Replace with your own stock and product details.','delivery_minutes'=>60,'cold_storage'=>$row[4]==='dairy','active'=>true,'featured'=>true]);
  $role=StoreRecord::in('roles')->firstOrCreate(['name'=>'delivery partner'],['data'=>['permissions'=>[]]]);
  if(!User::where('email','rider@novacart.test')->exists()){$u=User::create(['name'=>'Demo Delivery Partner','email'=>'rider@novacart.test','password'=>bcrypt('DemoRider!2026')]);$u->role_id=$role->id;$u->save();DB::table('role_user')->insert(['role_id'=>$role->id,'user_id'=>$u->id]);}
  foreach(['Mumbai demo'=>'400001,400002','Pune demo'=>'411001,411002'] as $name=>$pins){$zone=DB::table('delivery_zones')->where('name',$name)->first();$id=$zone?$zone->id:DB::table('delivery_zones')->insertGetId(['name'=>$name,'pincodes'=>$pins,'opens_at'=>'08:00','closes_at'=>'22:00','travel_minutes'=>30,'express'=>true,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
   for($day=0;$day<5;$day++)for($hour=8;$hour<22;$hour+=2){$start=today()->addDays($day)->setTime($hour,0);$end=$start->copy()->addHours(2);if($start->lte(now()))continue;DB::table('delivery_slots')->updateOrInsert(['zone_id'=>$id,'starts_at'=>$start->toDateTimeString(),'ends_at'=>$end->toDateTimeString()],['capacity'=>10,'created_at'=>now(),'updated_at'=>now()]);}
  }
 }
}
