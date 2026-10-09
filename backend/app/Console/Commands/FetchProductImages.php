<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class FetchProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:fetch-images 
                            {--source=local : Source mode: "local" (read storage/app/imports) or "api" (Pexels API)}
                            {--api-key= : Pexels API key override (for api mode)} 
                            {--force : Force re-fetch and replace existing images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch or import fashion images, post-process to 4:5 WebP (front, detail, full), and save photographer credits';

    /**
     * Negative keywords to avoid non-garment / non-model images in API mode.
     */
    protected array $negativeKeywords = [
        'flower', 'flora', 'blossom', 'rose', 'plant', 'petal', 'botanical', 
        'tree', 'nature', 'landscape', 'building', 'animal', 'dog', 'cat', 
        'food', 'drink', 'abstract', 'car', 'vehicle'
    ];

    /**
     * Positive keywords for API mode.
     */
    protected array $positiveKeywords = [
        'model', 'wearing', 'outfit', 'streetwear', 'fashion', 'jacket', 
        'pants', 'jeans', 'shirt', 'hoodie', 'sweater', 'cloak', 'coat', 
        'posing', 'portrait', 'man', 'woman', 'look', 'style', 'standing'
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceMode = strtolower($this->option('source') ?: 'local');

        $this->info("====================================================");
        $this->info("HOYOS Product Image Pipeline [Mode: " . strtoupper($sourceMode) . "]");
        $this->info("====================================================");

        $products = Product::with(['category', 'images'])->get();

        if ($products->isEmpty()) {
            $this->warn('No products found in the database.');
            return Command::SUCCESS;
        }

        if ($sourceMode === 'local') {
            return $this->handleLocalImport($products);
        } else {
            return $this->handleApiFetch($products);
        }
    }

    /**
     * Handle local file imports from storage/app/imports/{slug}.(jpg|png|webp)
     */
    protected function handleLocalImport($products): int
    {
        $importsDir = storage_path('app/imports');
        File::ensureDirectoryExists($importsDir);

        // Read credits from storage/app/imports/credits.csv
        $credits = $this->loadCreditsCsv($importsDir . '/credits.csv');

        $this->line("Reading files from: <comment>{$importsDir}</comment>");
        if (!empty($credits)) {
            $this->line("Found credits for " . count($credits) . " products in credits.csv");
        } else {
            $this->warn("Note: storage/app/imports/credits.csv not found or empty.");
        }

        $processedCount = 0;
        $missingSlugs = [];

        foreach ($products as $product) {
            $slug = $product->slug;
            $categorySlug = Str::slug($product->category->slug ?? 'streetwear');
            $outputDir = storage_path("app/public/products/{$categorySlug}/{$slug}");

            // Look for matching file: {slug}.jpg, {slug}.jpeg, {slug}.png, {slug}.webp
            $supportedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $foundFile = null;

            foreach ($supportedExtensions as $ext) {
                $candidate = "{$importsDir}/{$slug}.{$ext}";
                if (File::exists($candidate)) {
                    $foundFile = $candidate;
                    break;
                }
            }

            if (!$foundFile) {
                $missingSlugs[] = $slug;
                $this->warn("  [MISSING] No file found for slug: {$slug}");
                continue;
            }

            $this->line("  [PROCESSING] {$product->name} (" . basename($foundFile) . ")");

            // Run Pillow post-processor: front (4:5, head preserved), detail (torso zoom), full
            $success = $this->runPillowPostProcessor($foundFile, $outputDir, $slug);

            if (!$success) {
                $this->error("  [FAILED] Pillow post-processor failed for {$slug}");
                continue;
            }

            // Retrieve credit information
            $creditInfo = $credits[$slug] ?? [
                'photographer' => 'Original brand photo (demo use only)',
                'source_url' => '',
            ];

            $photographer = $creditInfo['photographer'] ?: 'Original brand photo (demo use only)';
            $sourceUrl = $creditInfo['source_url'] ?: '';

            // Update database records
            $this->updateProductImages($product, $categorySlug, $slug, $photographer, $sourceUrl);
            $processedCount++;
        }

        $this->newLine();
        $this->info("----------------------------------------------------");
        $this->info("Import Summary: Successfully processed {$processedCount} of {$products->count()} products.");

        // Print missing slugs
        if (!empty($missingSlugs)) {
            $this->newLine();
            $this->error("Missing Products (" . count($missingSlugs) . "):");
            $this->line("The following product slugs did not have an image in storage/app/imports/:");
            foreach ($missingSlugs as $mSlug) {
                $this->line("  - <fg=yellow>{$mSlug}</> (expected: storage/app/imports/{$mSlug}.jpg)");
            }
        }

        // Print full list of expected filenames for all products
        $this->newLine();
        $this->info("List of all expected product image files:");
        foreach ($products as $p) {
            $status = in_array($p->slug, $missingSlugs) ? '<fg=red>[MISSING]</>' : '<fg=green>[FOUND]</>';
            $this->line("  {$status} storage/app/imports/{$p->slug}.jpg");
        }

        return Command::SUCCESS;
    }

    /**
     * Parse credits.csv with columns: slug, photographer/credit, source_url
     */
    protected function loadCreditsCsv(string $csvPath): array
    {
        $credits = [];
        if (!File::exists($csvPath)) {
            return $credits;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return $credits;
        }

        $header = null;
        foreach ($lines as $index => $line) {
            $row = str_getcsv($line);
            if ($index === 0) {
                $header = array_map('trim', array_map('strtolower', $row));
                continue;
            }

            if (count($row) >= 2) {
                $slugIndex = array_search('slug', $header);
                $photoIndex = array_search('photographer', $header);
                if ($photoIndex === false) {
                    $photoIndex = array_search('credit', $header);
                }
                $urlIndex = array_search('source_url', $header);
                if ($urlIndex === false) {
                    $urlIndex = array_search('url', $header);
                }

                $slug = trim($row[$slugIndex !== false ? $slugIndex : 0] ?? '');
                $photographer = trim($row[$photoIndex !== false ? $photoIndex : 1] ?? '');
                $sourceUrl = trim($row[$urlIndex !== false ? $urlIndex : 2] ?? '');

                if (!empty($slug)) {
                    $credits[$slug] = [
                        'photographer' => $photographer,
                        'source_url' => $sourceUrl,
                    ];
                }
            }
        }

        return $credits;
    }

    /**
     * Write or update an entry in storage/app/imports/credits.csv
     */
    protected function updateCreditsCsv(string $slug, string $photographer, string $sourceUrl): void
    {
        $csvPath = storage_path('app/imports/credits.csv');
        $credits = $this->loadCreditsCsv($csvPath);
        $credits[$slug] = [
            'photographer' => $photographer,
            'source_url' => $sourceUrl,
        ];

        $lines = ["slug,photographer,source_url"];
        foreach ($credits as $s => $info) {
            $photo = str_contains($info['photographer'], ',') ? '"' . $info['photographer'] . '"' : $info['photographer'];
            $lines[] = "{$s},{$photo},{$info['source_url']}";
        }

        File::put($csvPath, implode("\n", $lines) . "\n");
    }

    /**
     * Handle Pexels API fetching mode.
     */
    protected function handleApiFetch($products): int
    {
        $apiKey = $this->option('api-key') ?: config('services.pexels.key') ?: env('PEXELS_API_KEY');

        if (empty($apiKey)) {
            $this->error('Error: Pexels API key not found.');
            $this->warn('Please set PEXELS_API_KEY in .env, pass --api-key="KEY", or use --source=local.');
            return Command::FAILURE;
        }

        $processedCount = 0;

        foreach ($products as $product) {
            $categorySlug = Str::slug($product->category->slug ?? 'streetwear');
            $slug = $product->slug;
            $productDir = storage_path("app/public/products/{$categorySlug}/{$slug}");

            $query = $this->getSearchQuery($product);
            $this->line("  [QUERYING PEXELS] {$product->name} -> '{$query}'");

            $photoData = $this->fetchBestPortraitPhoto($apiKey, $query);

            if (!$photoData) {
                $fallbackQuery = "streetwear {$categorySlug} fashion model";
                $photoData = $this->fetchBestPortraitPhoto($apiKey, $fallbackQuery);
            }

            if (!$photoData) {
                $this->error("  [FAILED] No suitable portrait garment photo found for {$product->name}");
                continue;
            }

            $photographer = $photoData['photographer'] ?? 'Pexels Creator';
            $sourceUrl = $photoData['url'] ?? 'https://www.pexels.com';
            $downloadUrl = $photoData['src']['original'] ?? $photoData['src']['large2x'];

            $tempFile = storage_path("app/temp_{$slug}.jpg");
            try {
                $imageContent = Http::timeout(45)->get($downloadUrl)->body();
                File::put($tempFile, $imageContent);
            } catch (\Exception $e) {
                $this->error("  [DOWNLOAD ERROR] {$e->getMessage()}");
                continue;
            }

            $success = $this->runPillowPostProcessor($tempFile, $productDir, $slug);
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }

            if ($success) {
                $this->updateProductImages($product, $categorySlug, $slug, $photographer, $sourceUrl);
                $this->updateCreditsCsv($slug, $photographer, $sourceUrl);
                $processedCount++;
            }
        }

        $this->info("API Fetching Complete: Processed {$processedCount} products.");
        return Command::SUCCESS;
    }

    /**
     * Map product to optimal search term for portrait model photography.
     */
    protected function getSearchQuery(Product $product): string
    {
        $specificQueries = [
            'neon-spray-moto-denim-set' => 'y2k streetwear model outfit baggy biker denim',
            'midnight-matrix-silhouette' => 'goth streetwear oversized model denim vest',
            'stone-guardian-armor-set' => 'dark techwear contour stitch jacket flared pants model',
            'boxy-cyber-jersey-tee' => 'boxy oversized jersey t-shirt model streetwear',
            'camo-tactical-cargo-set' => 'camo cargo pants sporty tee streetwear model',
            'dirty-acid-wash-denim-set' => 'acid wash denim jacket wide jeans streetwear model',
            'phantom-cyber-zip-hoodie' => 'distressed black zip up hoodie model streetwear',
            'nocturne-obsidian-duster-coat' => 'gothic duster trench coat model dark fashion',
            'vanguard-modular-chest-rig' => 'tactical chest rig harness streetwear model',
        ];

        return $specificQueries[$product->slug] ?? "streetwear outfit model posing";
    }

    /**
     * Query Pexels API and select the best portrait photo showing human model/garment.
     */
    protected function fetchBestPortraitPhoto(string $apiKey, string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])->timeout(30)->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'orientation' => 'portrait',
                'per_page' => 15,
                'page' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $photos = $data['photos'] ?? [];

            foreach ($photos as $photo) {
                $width = $photo['width'] ?? 0;
                $height = $photo['height'] ?? 0;
                $alt = strtolower($photo['alt'] ?? '');

                if ($height <= $width) {
                    continue;
                }

                $hasNegative = false;
                foreach ($this->negativeKeywords as $neg) {
                    if (str_contains($alt, $neg)) {
                        $hasNegative = true;
                        break;
                    }
                }

                if (!$hasNegative) {
                    return $photo;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Run Python Pillow post-processing script.
     */
    protected function runPillowPostProcessor(string $sourceFile, string $outputDir, string $slug): bool
    {
        $scriptPath = base_path('scripts/process_product_image.py');

        $process = new Process([
            'python',
            $scriptPath,
            $sourceFile,
            $outputDir,
            $slug,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("  Python Error: " . $process->getErrorOutput());
            return false;
        }

        return true;
    }

    /**
     * Update product_images table with the 3 processed versions:
     * front, detail, full.
     */
    protected function updateProductImages(Product $product, string $categorySlug, string $slug, string $photographer, string $sourceUrl): void
    {
        // Delete previous images for this product so gallery only shows this product
        $product->images()->delete();

        // 1. Front View (Primary 4:5 1200x1500)
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => "storage/products/{$categorySlug}/{$slug}/{$slug}-front.webp",
            'is_primary' => true,
            'display_order' => 0,
            'credit' => $photographer,
            'source_url' => $sourceUrl,
        ]);

        // 2. Detail View (Torso / Texture Crop 4:5 1200x1500)
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => "storage/products/{$categorySlug}/{$slug}/{$slug}-detail.webp",
            'is_primary' => false,
            'display_order' => 1,
            'credit' => $photographer,
            'source_url' => $sourceUrl,
        ]);

        // 3. Full Silhouette View (4:5 1200x1500)
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => "storage/products/{$categorySlug}/{$slug}/{$slug}-full.webp",
            'is_primary' => false,
            'display_order' => 2,
            'credit' => $photographer,
            'source_url' => $sourceUrl,
        ]);

        $this->line("  [SAVED] 3 gallery images (front, detail, full) registered for {$product->name}");
    }
}
