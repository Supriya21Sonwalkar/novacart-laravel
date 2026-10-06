<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\User;
class ValidationTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(\StoreSeeder::class);Notification::fake();}
 private function details(){return ['name'=>'Test Buyer','email'=>'buyer@example.test','phone'=>'9876543210','address'=>'12 Test Road','city'=>'Mumbai','state'=>'Maharashtra','pincode'=>'400001','type'=>'Home','password'=>'BuyerPass123!','password_confirmation'=>'BuyerPass123!'];}
 private function register($values=[]){return $this->post('/register',array_merge($this->details(),$values));}
 public function test_duplicate_phone_with_or_without_country_code_is_rejected(){
  $this->register(['phone'=>'+91 9876543210'])->assertSessionHasNoErrors();$u=User::where('email','buyer@example.test')->firstOrFail();$this->assertEquals('9876543210',$u->phone);$this->post('/logout');
  foreach(['9876543210','+919876543210','+91-9876543210'] as $phone){$this->register(['phone'=>$phone,'email'=>'second@example.test'])->assertSessionHasErrors('phone');$this->assertDatabaseMissing('users',['email'=>'second@example.test']);}
 }
 public function test_duplicate_email_is_rejected_ignoring_case(){
  $this->register()->assertSessionHasNoErrors();$this->post('/logout');$this->register(['email'=>'BUYER@EXAMPLE.TEST','phone'=>'9876543211'])->assertSessionHasErrors('email');
 }
 public function test_own_phone_can_be_kept_but_another_accounts_phone_is_rejected(){
  $this->register()->assertSessionHasNoErrors();$first=User::where('email','buyer@example.test')->first();$this->post('/account',$this->details())->assertSessionHasNoErrors();$this->post('/logout');
  $this->register(['phone'=>'9876543211','email'=>'second@example.test'])->assertSessionHasNoErrors();$this->post('/account',array_merge($this->details(),['email'=>'second@example.test']))->assertSessionHasErrors('phone');$this->assertEquals('9876543211',User::where('email','second@example.test')->first()->phone);$this->assertEquals('9876543210',$first->fresh()->phone);
 }
 public function test_invalid_fields_and_weak_passwords_are_rejected(){
  $this->register(['name'=>'123','email'=>'wrong','phone'=>'1234567890','address'=>'x','city'=>'123','state'=>'12','pincode'=>'000001','type'=>'bad','password'=>'password','password_confirmation'=>'password'])->assertSessionHasErrors(['name','email','phone','address','city','state','pincode','type','password']);
  $this->register(['password_confirmation'=>'Mismatch123!'])->assertSessionHasErrors('password');$this->assertDatabaseMissing('users',['email'=>'buyer@example.test']);
 }
 public function test_unique_constraint_protects_phone_even_outside_forms(){
  $this->register()->assertSessionHasNoErrors();$other=new User;$other->name='Other Buyer';$other->email='other@example.test';$other->password=bcrypt('BuyerPass123!');$other->phone='+919876543210';$this->expectException(\Illuminate\Database\QueryException::class);$other->save();
 }
}
