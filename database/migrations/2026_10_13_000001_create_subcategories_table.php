<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSubcategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('subcategories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->text('data')->nullable();
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unique(['category_id', 'name']);
            $table->index(['active', 'category_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategory_id')->nullable()->after('category_id');
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onDelete('set null');
            $table->index(['active', 'subcategory_id']);
        });

        $legacy = DB::table('categories')->get();
        foreach ($legacy as $category) {
            $data = $category->data ? json_decode($category->data, true) : [];
            $parentId = $data['parent_id'] ?? null;
            if (!$parentId) {
                continue;
            }

            $subcategoryId = DB::table('subcategories')->insertGetId([
                'category_id' => $parentId,
                'name' => $category->name,
                'active' => $category->active,
                'data' => json_encode($data),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]);

            DB::table('products')->where('category_id', $category->id)->update([
                'category_id' => $parentId,
                'subcategory_id' => $subcategoryId,
            ]);
        }

        DB::table('categories')->whereNotNull('parent_id')->delete();
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['subcategory_id']);
            $table->dropIndex(['active', 'subcategory_id']);
            $table->dropColumn('subcategory_id');
        });

        Schema::dropIfExists('subcategories');
    }
}
