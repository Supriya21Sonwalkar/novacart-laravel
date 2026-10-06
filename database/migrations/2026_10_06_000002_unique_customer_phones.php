<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class UniqueCustomerPhones extends Migration {
 public function up(){
  $rows=DB::table('users')->select('id','phone')->get();$seen=[];$updates=[];
  foreach($rows as $row){$phone=\App\Services\CustomerProfile::normalizePhone($row->phone);if($phone==='')$phone=null;
   if($phone!==null){if(isset($seen[$phone]))throw new RuntimeException('Duplicate existing phone numbers must be resolved before adding the unique index. No records were deleted.');$seen[$phone]=true;}
   $updates[$row->id]=$phone;
  }
  DB::transaction(function()use($updates){foreach($updates as $id=>$phone)DB::table('users')->where('id',$id)->update(['phone'=>$phone]);});
  Schema::table('users',function(Blueprint $table){$table->unique('phone');});
 }
 public function down(){Schema::table('users',function(Blueprint $table){$table->dropUnique('users_phone_unique');});}
}
