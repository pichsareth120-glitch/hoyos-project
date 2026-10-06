<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@hoyos.com'],
            [
                'name' => 'Hoyos Admin',
                'title' => 'Admin',
                'first_name' => 'Hoyos',
                'last_name' => 'Manager',
                'phone' => '+85512999888',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@hoyos.com'],
            [
                'name' => 'Cinnamon Aari',
                'title' => 'Prefer not to say',
                'first_name' => 'Cinnamon',
                'last_name' => 'Aari',
                'phone' => '+85516696969',
                'date_of_birth' => '2000-01-01',
                'role' => 'customer',
                'password' => Hash::make('password'),
            ]
        );

        // Address for customer
        Address::firstOrCreate(
            [
                'user_id' => $customer->id,
                'recipient_name' => 'Cinnamon Aari',
            ],
            [
                'recipient_phone' => '+85516696969',
                'province' => 'Phnom Penh',
                'address_line' => 'House 42, St 155, Toul Tompoung 1, Chamkarmon',
                'notes' => 'Call 10 minutes before delivery.',
                'is_default' => true,
            ]
        );

        // 2. Create Categories
        $y2k = Category::updateOrCreate(
            ['slug' => 'y2k'],
            [
                'name' => 'Y2K',
                'description' => 'Cyber-rave nostalgia, baggy silhouettes, and early 2000s rebellious energy.',
                'banner_image' => 'images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg',
            ]
        );

        $goth = Category::updateOrCreate(
            ['slug' => 'goth'],
            [
                'name' => 'GOTH',
                'description' => 'Dark gothic edge, raw distressed textures, and subtle demon-inspired aesthetics.',
                'banner_image' => 'images/hoyos/43087ed8c434aaf444e0cf52ffb1cd37.jpg',
            ]
        );

        $streetwear = Category::updateOrCreate(
            ['slug' => 'streetwear'],
            [
                'name' => 'STREETWEAR',
                'description' => 'Modern oversized Gen Z cuts designed to make your outfit speak louder than words.',
                'banner_image' => 'images/hoyos/1191d205-189e-4e8d-a3c5-ad5d088afc23.jpg',
            ]
        );

        // 3. Products
        $productsData = [
            [
                'category_id' => $y2k->id,
                'name' => 'Neon Spray Moto Denim Set',
                'slug' => 'neon-spray-moto-denim-set',
                'description' => "Futuristic cyberpunk moto denim set featuring a dark washed denim biker jacket with signature yellow-sand spray wash across the chest and ribbed sleeve accents, paired with matching flared dark denim pants with spray fade detailing and chunky platform boot styling.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nSlight stretch for a comfortable fit. Size up for a looser style.",
                'price' => 145.00,
                'compare_at_price' => 165.00,
                'collection_name' => 'Neon Phantom Drift',
                'rating' => 5.00,
                'review_count' => 12,
                'is_featured' => true,
                'is_new_arrival' => true,
                'images' => [
                    'storage/products/y2k/neon-spray-moto-denim-set/neon-spray-moto-denim-set-front.webp',
                    'storage/products/y2k/neon-spray-moto-denim-set/neon-spray-moto-denim-set-detail.webp',
                    'storage/products/y2k/neon-spray-moto-denim-set/neon-spray-moto-denim-set-full.webp',
                ],
            ],
            [
                'category_id' => $goth->id,
                'name' => 'Midnight Matrix Silhouette',
                'slug' => 'midnight-matrix-silhouette',
                'description' => "Hoyos signature dark aesthetic featuring a sleeveless distressed vintage-wash denim trucker vest with brass metal buttons and mud-yellow fade accents, paired with ultra-wide-leg denim trousers with yellow-tinted side-seam fade and raw distressed hems.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nRelaxed oversized cut with tailored waist drape.",
                'price' => 145.00,
                'compare_at_price' => null,
                'collection_name' => 'Stone Guardian Core',
                'rating' => 5.00,
                'review_count' => 18,
                'is_featured' => true,
                'is_new_arrival' => false,
                'images' => [
                    'storage/products/goth/midnight-matrix-silhouette/midnight-matrix-silhouette-front.webp',
                    'storage/products/goth/midnight-matrix-silhouette/midnight-matrix-silhouette-detail.webp',
                    'storage/products/goth/midnight-matrix-silhouette/midnight-matrix-silhouette-full.webp',
                ],
            ],
            [
                'category_id' => $goth->id,
                'name' => 'Stone Guardian Armor Set',
                'slug' => 'stone-guardian-armor-set',
                'description' => "Heavy structured streetwear armor set featuring a dark indigo boxy workwear jacket with high-contrast white spiderweb geometric contour stitching and chrome buttons, paired with matching exaggerated flared contour pants.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nStructured shoulder drape and heavyweight indigo twill.",
                'price' => 165.00,
                'compare_at_price' => 190.00,
                'collection_name' => 'Stone Guardian Core',
                'rating' => 4.90,
                'review_count' => 9,
                'is_featured' => true,
                'is_new_arrival' => true,
                'images' => [
                    'storage/products/goth/stone-guardian-armor-set/stone-guardian-armor-set-front.webp',
                    'storage/products/goth/stone-guardian-armor-set/stone-guardian-armor-set-detail.webp',
                    'storage/products/goth/stone-guardian-armor-set/stone-guardian-armor-set-full.webp',
                ],
            ],
            [
                'category_id' => $streetwear->id,
                'name' => 'Boxy Cyber Jersey Tee',
                'slug' => 'boxy-cyber-jersey-tee',
                'description' => "Oversized drop-shoulder athletic jersey tee in washed charcoal featuring distressed chrome 'EASYNDEASY' arch typography and bold '24' star print, paired with tactical camo cargo jorts and slouch socks.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nOversized drop-shoulder streetwear fit.",
                'price' => 95.00,
                'compare_at_price' => null,
                'collection_name' => 'Neon Phantom Drift',
                'rating' => 5.00,
                'review_count' => 24,
                'is_featured' => true,
                'is_new_arrival' => false,
                'images' => [
                    'storage/products/streetwear/boxy-cyber-jersey-tee/boxy-cyber-jersey-tee-front.webp',
                    'storage/products/streetwear/boxy-cyber-jersey-tee/boxy-cyber-jersey-tee-detail.webp',
                    'storage/products/streetwear/boxy-cyber-jersey-tee/boxy-cyber-jersey-tee-full.webp',
                ],
            ],
            [
                'category_id' => $y2k->id,
                'name' => 'Camo Tactical Cargo Set',
                'slug' => 'camo-tactical-cargo-set',
                'description' => "Harajuku rave and tactical streetwear ensemble featuring oversized camouflage cargo pants with utility flap pockets, an athletic-striped sporty tee with contrast yellow piping, and a waist-tied flannel accent with chrome wallet chain.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nExtra baggy leg silhouette with adjustable hems.",
                'price' => 135.00,
                'compare_at_price' => 150.00,
                'collection_name' => 'Neon Phantom Drift',
                'rating' => 4.85,
                'review_count' => 14,
                'is_featured' => false,
                'is_new_arrival' => true,
                'images' => [
                    'storage/products/y2k/camo-tactical-cargo-set/camo-tactical-cargo-set-front.webp',
                    'storage/products/y2k/camo-tactical-cargo-set/camo-tactical-cargo-set-detail.webp',
                    'storage/products/y2k/camo-tactical-cargo-set/camo-tactical-cargo-set-full.webp',
                ],
            ],
            [
                'category_id' => $streetwear->id,
                'name' => 'Dirty Acid Wash Denim Set',
                'slug' => 'dirty-acid-wash-denim-set',
                'description' => "Vintage grunge denim 2-piece set featuring a mud-olive acid wash denim trucker jacket with drop shoulders and sun-faded chest panels, worn over a dark undershirt with matching wide-leg knee-paneled acid-washed jeans.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nHeavyweight denim with relaxed drape.",
                'price' => 125.00,
                'compare_at_price' => null,
                'collection_name' => 'Stone Guardian Core',
                'rating' => 5.00,
                'review_count' => 8,
                'is_featured' => false,
                'is_new_arrival' => false,
                'images' => [
                    'storage/products/streetwear/dirty-acid-wash-denim-set/dirty-acid-wash-denim-set-front.webp',
                    'storage/products/streetwear/dirty-acid-wash-denim-set/dirty-acid-wash-denim-set-detail.webp',
                    'storage/products/streetwear/dirty-acid-wash-denim-set/dirty-acid-wash-denim-set-full.webp',
                ],
            ],
            [
                'category_id' => $streetwear->id,
                'name' => 'Phantom Cyber Distressed Zip-Hoodie',
                'slug' => 'phantom-cyber-zip-hoodie',
                'description' => "Heavyweight 520 GSM French terry boxy zip hoodie in washed obsidian. Engineered with hand-distressed raw hems, tonal cybernetic graphics on back and sleeves, and custom double-ended chrome zipper hardware.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nBoxy drop-shoulder streetwear cut.",
                'price' => 115.00,
                'compare_at_price' => 135.00,
                'collection_name' => 'Neon Phantom Drift',
                'rating' => 5.00,
                'review_count' => 8,
                'is_featured' => true,
                'is_new_arrival' => true,
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                'images' => [
                    'images/hoyos/placeholder-item.png',
                ],
            ],
            [
                'category_id' => $goth->id,
                'name' => 'Nocturne Obsidian Duster Trench Coat',
                'slug' => 'nocturne-obsidian-duster-coat',
                'description' => "Ankle-length architectural gothic duster trench coat in weather-resistant coated canvas. Features dramatic structured storm flaps, high stand collar with throat latch, detachable tactical waist harness, and matte black hardware.",
                'size_guide' => "• M: 55–70 kg\n• L: 70–80 kg\n• XL: 80–90 kg\n• XXL: 90–105 kg\nTailored floor-length duster silhouette.",
                'price' => 185.00,
                'compare_at_price' => 210.00,
                'collection_name' => 'Stone Guardian Core',
                'rating' => 4.90,
                'review_count' => 15,
                'is_featured' => true,
                'is_new_arrival' => true,
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                'images' => [
                    'images/hoyos/placeholder-item.png',
                ],
            ],
            [
                'category_id' => $y2k->id,
                'name' => 'Vanguard Modular Tactical Chest Rig',
                'slug' => 'vanguard-modular-chest-rig',
                'description' => "Modular cross-chest utility harness crafted from rugged ballistic nylon. Outfitted with detachable magnetic quick-release pouches, anodized silver D-rings, and adjustable webbing straps with reflective demon iconography.",
                'size_guide' => "• One size: Fully adjustable cross-back harness fits chest circumference 80–125 cm.",
                'price' => 65.00,
                'compare_at_price' => null,
                'collection_name' => 'Neon Phantom Drift',
                'rating' => 5.00,
                'review_count' => 21,
                'is_featured' => true,
                'is_new_arrival' => true,
                'sizes' => ['One size'],
                'images' => [
                    'images/hoyos/placeholder-item.png',
                ],
            ],
        ];

        foreach ($productsData as $data) {
            $images = $data['images'];
            $sizes = $data['sizes'] ?? ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            unset($data['images'], $data['sizes']);

            $product = Product::updateOrCreate(['slug' => $data['slug']], $data);

            // Create Variants
            foreach ($sizes as $size) {
                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'size' => $size,
                    ],
                    [
                        'sku' => strtoupper($product->slug . '-' . str_replace(' ', '', $size)),
                        'stock_quantity' => 25,
                        'price_adjustment' => 0.00,
                    ]
                );
            }

            // Create Images with seeded credits directly into database
            foreach ($images as $index => $path) {
                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'image_path' => $path,
                    ],
                    [
                        'is_primary' => $index === 0,
                        'display_order' => $index,
                        'credit' => 'Original brand photo (demo use only)',
                        'source_url' => null,
                    ]
                );
            }
        }

        // 4. Create an Initial Sample Order with J&T Tracking Code for testing Customer UI
        $sampleProduct = Product::where('slug', 'neon-spray-moto-denim-set')->first();
        $sampleVariant = $sampleProduct->variants()->where('size', 'XXL')->first();

        $order = Order::firstOrCreate(
            ['order_number' => 'HOYOS-000123'],
            [
                'user_id' => $customer->id,
                'guest_token' => 'sample-guest-token-hoyos-000123',
                'customer_name' => 'Cinnamon Aari',
                'customer_phone' => '+85516696969',
                'customer_email' => 'customer@hoyos.com',
                'shipping_address' => 'House 42, St 155, Toul Tompoung 1, Chamkarmon',
                'province' => 'Phnom Penh',
                'delivery_notes' => 'Call before delivery',
                'subtotal' => 145.00,
                'shipping_fee' => 0.00,
                'total_amount' => 145.00,
                'order_status' => 'shipped',
                'payment_method' => 'aba_khqr',
                'payment_status' => 'paid',
            ]
        );

        OrderItem::firstOrCreate(
            [
                'order_id' => $order->id,
                'product_variant_id' => $sampleVariant->id,
            ],
            [
                'product_name' => $sampleProduct->name,
                'variant_size' => 'XXL',
                'unit_price' => 145.00,
                'quantity' => 1,
                'subtotal' => 145.00,
            ]
        );

        Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => 'aba_khqr',
                'transaction_id' => 'ABA-TX-' . strtoupper(Str::random(8)),
                'amount' => 145.00,
                'currency' => 'USD',
                'status' => 'paid',
                'paid_at' => now()->subDay(),
            ]
        );

        Shipment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'carrier' => 'J&T Express',
                'tracking_number' => 'JT123456789KH',
                'status' => Shipment::STATUS_SHIPPED,
                'shipped_at' => now()->subHours(12),
            ]
        );
    }
}
