<?php
use Illuminate\Database\Seeder;
use App\StoreRecord;
use App\Product;
use App\User;
use Illuminate\Support\Facades\Hash;
class StoreSeeder extends Seeder {
 public function run(){
 $admin=StoreRecord::in('roles')->firstOrCreate(['name'=>'admin'],['data'=>['permissions'=>array_merge(['dashboard'],array_keys(config('store.modules')))]]);$customer=StoreRecord::in('roles')->firstOrCreate(['name'=>'customer'],['data'=>['permissions'=>[]]]);
 foreach(array_merge(['dashboard'],array_keys(config('store.modules'))) as $p)StoreRecord::in('permissions')->firstOrCreate(['name'=>$p],['data'=>[]]);
 $email=env('DEMO_ADMIN_EMAIL','admin@novacart.test');if(!User::where('email',$email)->exists()){$u=new User;$u->name='Store Admin';$u->email=$email;$u->password=Hash::make(env('DEMO_ADMIN_PASSWORD','DemoStore!2026'));$u->role_id=$admin->id;$u->email_verified_at=now();$u->save();DB::table('role_user')->insert(['role_id'=>$admin->id,'user_id'=>$u->id]);}
 StoreRecord::in('settings')->firstOrCreate(['name'=>'store'],['data'=>['name'=>'NovaCart','tax'=>5,'free_threshold'=>2999,'payment_methods'=>'Test card / UPI,Cash on delivery (test)','support_email'=>'','about'=>'A considered collection of everyday essentials.','terms'=>'This is a demonstration store. Orders are for testing only.','privacy'=>'Your account and shopping data are stored to provide the store features.','returns'=>'Request a return within 7 days of delivery.','seo_description'=>'Shop considered everyday essentials.']]);
 $cats=[];foreach(['Electronics','Fashion','Home & Living'] as $i=>$name)$cats[]=StoreRecord::in('categories')->firstOrCreate(['name'=>$name],['data'=>['position'=>$i]]);
 $brands=[];foreach(['Studio','Everyday','Form'] as $name)$brands[]=StoreRecord::in('brands')->firstOrCreate(['name'=>$name],['data'=>[]]);
 StoreRecord::in('coupons')->firstOrCreate(['name'=>'WELCOME10'],['data'=>['type'=>'percentage','value'=>10,'minimum'=>1000,'maximum'=>500,'limit'=>100]]);
 StoreRecord::in('shipping_methods')->firstOrCreate(['name'=>'Standard delivery'],['data'=>['charge'=>99,'free_threshold'=>2999,'estimated_days'=>'3–5','zones'=>'India']]);StoreRecord::in('shipping_methods')->firstOrCreate(['name'=>'Express delivery'],['data'=>['charge'=>199,'estimated_days'=>'1–2','zones'=>'India']]);
 $rows=[['Studio Wireless Headphones','NC-AUD-001',349900,499900,28,'https://hotsound.cstatic.io/media/image/92/b3/d3/NEXTaudiocomX4W1.jpg','Best seller','Cloud white, Midnight','Your everyday listening companion. Soft ear cushions and wireless freedom.'],['Everyday City Backpack','NC-BAG-002',249900,329900,16,'https://portpearl.com/cdn/shop/products/all-over-print-minimalist-backpack-white-front-62629d0ab7d98.jpg?v=1650629905','New arrival','Charcoal','A clean, practical backpack with room for your daily essentials.'],['Daily Ceramic Mug','NC-HOM-003',49900,69900,45,'https://www.craftclothing.ph/cdn/shop/files/T32-Wht-F_194bfc0e-3400-4022-8b82-c71b57a3d398.png?v=1740987704','Everyday essential','White','Make a little room for a slow morning with a simple ceramic mug.']];
 foreach($rows as $i=>$p)Product::firstOrCreate(['sku'=>$p[1]],['name'=>$p[0],'price'=>$p[2],'old_price'=>$p[3],'stock'=>$p[4],'image'=>$p[5],'badge'=>$p[6],'variants'=>$p[7],'description'=>$p[8],'specifications'=>'Sample product. Replace with your own specifications.','category_id'=>$cats[$i]->id,'brand_id'=>$brands[$i]->id,'featured'=>true,'active'=>true]);
 }
}
