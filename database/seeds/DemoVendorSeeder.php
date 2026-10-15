<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
class DemoVendorSeeder extends Seeder {
 public function run(){
  DB::transaction(function(){
   $role=\App\StoreRecord::in('roles')->where('name','vendor')->firstOrFail();$email='vendor@novacart.test';$user=\App\User::where('email',$email)->first();
   if(!$user){$user=\App\User::create(['name'=>'Demo Vendor','email'=>$email,'password'=>Hash::make(env('DEMO_VENDOR_PASSWORD','VendorStore!2026'))]);$user->role_id=$role->id;$user->email_verified_at=now();$user->save();DB::table('role_user')->insert(['user_id'=>$user->id,'role_id'=>$role->id]);}
   if((int)$user->role_id!==(int)$role->id)throw new RuntimeException('The demo email already belongs to a different role. No account changes made.');
   if(!\App\Vendor::where('user_id',$user->id)->exists())\App\Vendor::create(['name'=>'Demo Vendor Store','user_id'=>$user->id,'active'=>true,'data'=>['contact_person'=>'Demo Vendor','email'=>$email,'description'=>'Local vendor dashboard demonstration. Assign products from Admin to connect real stock and future orders.']]);
  });
 }
}
