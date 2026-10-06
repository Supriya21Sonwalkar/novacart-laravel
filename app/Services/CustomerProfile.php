<?php
namespace App\Services;
use App\Address;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class CustomerProfile {
 public static function normalizePhone($value){
  if(!is_string($value))return $value;
  $value=trim($value);
  if(preg_match('/^(?:\+91[ -]?)?[6-9][0-9]{9}$/',$value))return substr(preg_replace('/\D/','',$value),-10);
  return $value;
 }
 public static function normalizeRequest($request){
  $values=[];
  if(is_string($request->input('email')))$values['email']=strtolower(trim($request->input('email')));
  if($request->has('phone'))$values['phone']=self::normalizePhone($request->input('phone'));
  $request->merge($values);
 }
 public static function rules($id=null){return [
  'name'=>['bail','required','string','min:2','max:100',"regex:/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/u"],'email'=>['bail','required','string','email','max:190',Rule::unique('users')->ignore($id)],
  'phone'=>['bail','required','string','regex:/^[6-9][0-9]{9}$/',Rule::unique('users')->ignore($id)],
  'address'=>'required|string|min:5|max:255','city'=>['required','string','min:2','max:100',"regex:/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/u"],'state'=>['required','string','min:2','max:100',"regex:/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/u"],
  'pincode'=>'required|regex:/^[1-9][0-9]{5}$/','type'=>'required|in:Home,Office,Other'
 ];}
 public static function messages(){return ['phone.regex'=>'Enter a valid 10-digit Indian mobile number starting with 6, 7, 8 or 9.','phone.unique'=>'This mobile number is already registered. Please use a different number or sign in.','email.unique'=>'This email address is already registered. Please sign in or use a different email.','name.regex'=>'Use letters, spaces, apostrophes, dots or hyphens for your name.','city.regex'=>'Enter a valid city name using letters.','state.regex'=>'Enter a valid state name using letters.','pincode.regex'=>'Enter a valid six-digit PIN code starting with 1–9.','password.regex'=>'Your password must include an uppercase letter, a lowercase letter and a number.'];}
 public static function address(User $user){return Address::where('user_id',$user->id)->where('is_default',true)->first();}
 public static function addressRules(){
  $rules=self::rules();unset($rules['email']);$rules['phone']=['required','string','regex:/^[6-9][0-9]{9}$/'];return $rules;
 }
 public static function complete($user){
  if(!$user||!$user->active)return false;
  $a=self::address($user);if(!$a)return false;
  $data=$a->only(['address','city','state','pincode','type']);
  $data+=['name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone];
  $rules=self::rules($user->id);$rules['email']='required|email|max:190';$data['phone']=self::normalizePhone($data['phone']);
  return Validator::make($data,$rules)->passes()&&Validator::make(['name'=>$a->name,'phone'=>self::normalizePhone($a->phone)],['name'=>$rules['name'],'phone'=>'required|regex:/^[6-9][0-9]{9}$/'])->passes();
 }
 public static function save(User $user,array $data){
  try{DB::transaction(function()use($user,$data){
   $data['phone']=self::normalizePhone($data['phone']);$data['email']=strtolower(trim($data['email']));
   $user->name=$data['name'];$user->phone=$data['phone'];
   if($user->email!==$data['email'])$user->email_verified_at=null;
   $user->email=$data['email'];$user->save();
   $a=self::address($user)?:new Address;
   Address::where('user_id',$user->id)->update(['is_default'=>false]);
   if($a->exists)$a->refresh();
   $a->fill(array_intersect_key($data,array_flip(['name','phone','address','city','state','pincode','type'])));
   $a->user_id=$user->id;$a->session_key=Commerce::owner();$a->is_default=true;$a->save();
  });}catch(\Illuminate\Database\QueryException $e){self::duplicateError($e);}
 }
 public static function duplicateError($e){
  foreach(['phone','email'] as $field)if(strpos($e->getMessage(),'users_'.$field.'_unique')!==false||strpos($e->getMessage(),'users.'.$field)!==false)throw \Illuminate\Validation\ValidationException::withMessages([$field=>'This '.$field.' is already registered to another account.']);
  throw $e;
 }
 public static function requireComplete(){if(!self::complete(auth()->user()))Commerce::error('Complete your contact details and default address in Your account before shopping.');}
}
