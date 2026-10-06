<?php
namespace App\Services;
use App\Address;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class CustomerProfile {
 public static function rules($id=null){return [
  'name'=>'required|string|max:100','email'=>['required','email','max:190',Rule::unique('users')->ignore($id)],
  'phone'=>['required','string','regex:/^(?:\+91[ -]?)?[6-9][0-9]{9}$/'],
  'address'=>'required|string|max:255','city'=>'required|string|max:100','state'=>'required|string|max:100',
  'pincode'=>'required|regex:/^[1-9][0-9]{5}$/','type'=>'required|in:Home,Office,Other'
 ];}
 public static function address(User $user){return Address::where('user_id',$user->id)->where('is_default',true)->first();}
 public static function complete($user){
  if(!$user||!$user->active)return false;
  $a=self::address($user);if(!$a)return false;
  $data=$a->only(['address','city','state','pincode','type']);
  $data+=['name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone];
  $rules=self::rules($user->id);$rules['email']='required|email|max:190';
  return Validator::make($data,$rules)->passes()&&Validator::make($a->only(['name','phone']),['name'=>$rules['name'],'phone'=>$rules['phone']])->passes();
 }
 public static function save(User $user,array $data){
  DB::transaction(function()use($user,$data){
   $user->name=$data['name'];$user->phone=$data['phone'];
   if($user->email!==$data['email'])$user->email_verified_at=null;
   $user->email=$data['email'];$user->save();
   $a=self::address($user)?:new Address;
   Address::where('user_id',$user->id)->update(['is_default'=>false]);
   if($a->exists)$a->refresh();
   $a->fill(array_intersect_key($data,array_flip(['name','phone','address','city','state','pincode','type'])));
   $a->user_id=$user->id;$a->session_key=Commerce::owner();$a->is_default=true;$a->save();
  });
 }
 public static function requireComplete(){if(!self::complete(auth()->user()))Commerce::error('Complete your contact details and default address in Your account before shopping.');}
}
