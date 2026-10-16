<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Product;
use App\Support\SecondaryFailure;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductWriter
{
    public static function save($item, array $values, $gallery, $upload = null)
    {
        $urls = array_values(array_filter(array_map('trim', explode(',', $gallery))));
        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL) && substr($url, 0, 9) !== '/storage/') {
                Commerce::error('Gallery images must be valid URLs.');
            }
        }
        $path = null;
        try {
            if ($upload) {
                $path = 'products/' . $upload->hashName();
                $stored = $upload->storeAs('products', basename($path), 'public');
                if (!$stored) {
                    throw new \RuntimeException('Product image could not be stored.');
                }
                $values['image'] = '/storage/' . $path;
            }
            $id = $item ? $item->id : null;
            return DB::transaction(function () use ($id, $values, $urls) {
                // Reload on every attempt: rolled-back Eloquent objects may contain stale state.
                $product = $id ? Product::withTrashed()->where('id', $id)->lockForUpdate()->firstOrFail() : new Product();
                $before = $product->exists ? $product->stock : 0;
                if ($product->trashed() && $values['active']) {
                    $product->restore();
                }
                $target = $values['stock'];
                $values['stock'] = $before;
                $product->fill($values)->save();
                BatchStock::adjust($product, $target);
                $product->refresh();
                $product->images()->delete();
                foreach ($urls as $position => $url) {
                    $product->images()->create(['url' => $url, 'position' => $position]);
                }
                if ($before !== $product->stock) {
                    VendorFlow::recordInventory($product, $product->stock - $before, 'Product saved');
                }
                Commerce::log('Saved products', ['id' => $product->id, 'name' => $product->name]);
                return $product;
            }, 3);
        } catch (Throwable $original) {
            if ($path) {
                try {
                    // A lost connection / commit acknowledgement is ambiguous: never delete a referenced file.
                    if (!Product::withTrashed()->where('image', '/storage/' . $path)->exists() && !Storage::disk('public')->delete($path)) {
                        throw new \RuntimeException('Product upload cleanup failed.');
                    }
                } catch (Throwable $cleanupFailure) {
                    SecondaryFailure::report('Product upload requires cleanup review', $original, $cleanupFailure);
                }
            }
            throw $original;
        }
    }
}
