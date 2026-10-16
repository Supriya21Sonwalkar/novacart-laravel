<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Review;
use App\Support\SecondaryFailure;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReviewWriter
{
    public static function save($product, $id, array $values, $upload = null)
    {
        $userId = auth()->id();

        // Reject an unauthorized edit before storing a file.
        if ($id) {
            Review::where('user_id', $userId)->where('product_id', $product->id)->findOrFail($id);
        }
        unset($values['image']);
        $path = null;
        try {
            if ($upload) {
                $path = 'reviews/' . $upload->hashName();
                $stored = $upload->storeAs('reviews', basename($path), 'public');
                if (!$stored) {
                    throw new \RuntimeException('Review image could not be stored.');
                }
                $values['image'] = '/storage/' . $path;
            }
            return DB::transaction(function () use ($product, $id, $userId, $values) {
                $review = $id ? Review::where('user_id', $userId)->where('product_id', $product->id)->lockForUpdate()->findOrFail($id) : new Review();
                $review->fill($values);
                $review->user_id = $userId;
                $review->product_id = $product->id;
                $review->approved = false;
                $review->save();
                VendorFlow::notify($product->vendor, 'A review was submitted for ' . $product->name, ['kind' => 'reviews']);
                return $review;
            }, 3);
        } catch (Throwable $original) {
            if ($path) {
                try {
                    if (!Review::where('image', '/storage/' . $path)->exists() && !Storage::disk('public')->delete($path)) {
                        throw new \RuntimeException('Review upload cleanup failed.');
                    }
                } catch (Throwable $cleanupFailure) {
                    SecondaryFailure::report('Review upload requires cleanup review', $original, $cleanupFailure);
                }
            }
            throw $original;
        }
    }
}
