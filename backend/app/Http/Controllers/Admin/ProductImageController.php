<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Upload and store product image(s).
     */
    public function store(UploadProductImageRequest $request, int $productId)
    {
        $product = Product::withTrashed()->findOrFail($productId);

        DB::transaction(function () use ($request, $product) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            $maxOrder = (int) $product->images()->max('display_order');
            $files = $request->file('images');
            $requestedPrimary = $request->boolean('is_primary');

            foreach ($files as $index => $file) {
                // Generate random unique filename
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $extension = 'webp';
                }
                $filename = Str::random(40) . '.' . $extension;

                // Store on public disk inside products folder
                $storedPath = $file->storeAs('products', $filename, 'public');
                $dbPath = 'storage/' . $storedPath;

                // Determine is_primary: exactly one primary enforced
                $isPrimary = false;
                if ($requestedPrimary && $index === 0) {
                    $prevPrimary = ProductImage::where('product_id', $product->id)->where('is_primary', true)->first();
                    if ($prevPrimary) {
                        $prevPrimary->update(['is_primary' => false]);
                        AdminAuditLogger::log(
                            'images',
                            'demote_primary',
                            'product_images',
                            $prevPrimary->id,
                            ['is_primary' => true],
                            ['is_primary' => false]
                        );
                    }
                    ProductImage::where('product_id', $product->id)
                        ->where('id', '!=', $prevPrimary?->id)
                        ->update(['is_primary' => false]);
                    $isPrimary = true;
                    $hasPrimary = true;
                } elseif (!$hasPrimary && $index === 0) {
                    $isPrimary = true;
                    $hasPrimary = true;
                }

                $image = ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $dbPath,
                    'is_primary' => $isPrimary,
                    'display_order' => $request->filled('display_order') ? ((int)$request->input('display_order') + $index) : (++$maxOrder),
                    'credit' => $request->input('credit'),
                    'source_url' => $request->input('source_url'),
                ]);

                AdminAuditLogger::log(
                    'images',
                    'upload',
                    'product_images',
                    $image->id,
                    null,
                    $image->toArray()
                );
            }
        });

        return back()->with('success', 'Image(s) uploaded successfully.');
    }

    /**
     * Update image metadata (credit, source_url, display_order).
     */
    public function update(Request $request, int $id)
    {
        $image = ProductImage::findOrFail($id);

        $validated = $request->validate([
            'credit' => 'nullable|string|max:255',
            'source_url' => 'nullable|url|max:500',
            'display_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($image, $validated) {
            $old = $image->only(['credit', 'source_url', 'display_order']);
            $action = (array_key_exists('display_order', $validated) && (int)$validated['display_order'] !== (int)$old['display_order'])
                ? 'reorder'
                : 'update';

            $image->update($validated);

            AdminAuditLogger::log(
                'images',
                $action,
                'product_images',
                $image->id,
                $old,
                $image->only(['credit', 'source_url', 'display_order'])
            );
        });

        return back()->with('success', 'Image metadata updated.');
    }

    /**
     * Set the selected image as primary (exactly one primary per product).
     */
    public function setPrimary(int $id)
    {
        $image = ProductImage::findOrFail($id);

        DB::transaction(function () use ($image) {
            $prevPrimary = ProductImage::where('product_id', $image->product_id)
                ->where('is_primary', true)
                ->where('id', '!=', $image->id)
                ->first();

            if ($prevPrimary) {
                $prevPrimary->update(['is_primary' => false]);
                AdminAuditLogger::log(
                    'images',
                    'demote_primary',
                    'product_images',
                    $prevPrimary->id,
                    ['is_primary' => true],
                    ['is_primary' => false]
                );
            }

            ProductImage::where('product_id', $image->product_id)
                ->where('id', '!=', $image->id)
                ->where('id', '!=', $prevPrimary?->id)
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);

            AdminAuditLogger::log(
                'images',
                'set_primary',
                'product_images',
                $image->id,
                ['is_primary' => false],
                ['is_primary' => true]
            );
        });

        return back()->with('success', 'Primary product image updated.');
    }

    /**
     * Delete an image from database and disk.
     * If the deleted image was primary, promote the next image.
     */
    public function destroy(int $id)
    {
        $image = ProductImage::findOrFail($id);

        DB::transaction(function () use ($image) {
            $wasPrimary = $image->is_primary;
            $productId = $image->product_id;
            $oldData = $image->toArray();

            // Delete physical file from public disk (strip 'storage/' prefix)
            $relativeDiskPath = preg_replace('#^storage/#', '', $image->image_path);
            if (!empty($relativeDiskPath) && !str_starts_with($relativeDiskPath, 'images/')) {
                Storage::disk('public')->delete($relativeDiskPath);
            }

            $image->delete();

            // If the deleted image was primary, promote the next one
            if ($wasPrimary) {
                $next = ProductImage::where('product_id', $productId)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->first();

                if ($next) {
                    $next->update(['is_primary' => true]);

                    AdminAuditLogger::log(
                        'images',
                        'promote_primary',
                        'product_images',
                        $next->id,
                        ['is_primary' => false],
                        ['is_primary' => true]
                    );
                }
            }

            AdminAuditLogger::log(
                'images',
                'delete',
                'product_images',
                $image->id,
                $oldData,
                null
            );
        });

        return back()->with('success', 'Image deleted.');
    }
}
