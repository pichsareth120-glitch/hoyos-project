<x-admin-layout title="Edit: {{ $product->name }}">
    <div 
        class="space-y-6 max-w-7xl mx-auto" 
        x-data="{
            name: '{{ addslashes(old('name', $product->name)) }}',
            slug: '{{ addslashes(old('slug', $product->slug)) }}',
            slugCustom: {{ old('slug') && old('slug') !== $product->slug ? 'true' : 'false' }},
            archiveModalOpen: false,
            editVariantModalOpen: false,
            activeVariant: { id: null, size: '', sku: '', price_adjustment: 0.00 },
            selectedUploadCount: 0,
            selectedFileNames: [],
            handleFiles(e) {
                const files = e.target.files;
                this.selectedUploadCount = files.length;
                this.selectedFileNames = Array.from(files).map(f => f.name);
            },
            openEditVariant(v) {
                this.activeVariant = { ...v };
                this.editVariantModalOpen = true;
            }
        }"
    >

        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/[0.08]">
            <div>
                <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-xs font-mono text-zinc-400 hover:text-white transition-colors mb-2">
                    <span>←</span>
                    <span>Back to Products List</span>
                </a>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-display text-white uppercase tracking-wider">
                        {{ $product->name }}
                    </h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase font-bold border {{ $product->is_active ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-zinc-500/15 text-zinc-400 border-zinc-500/30' }}">
                        {{ $product->is_active ? 'Active' : 'Draft' }}
                    </span>
                    @if($product->trashed())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                            Archived
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if(!$product->trashed() && $product->is_active)
                    <x-ui.button 
                        :href="route('products.show', $product->slug)" 
                        target="_blank"
                        variant="secondary"
                        size="sm"
                        class="font-mono text-xs"
                    >
                        <span>View on Storefront</span>
                        <span>↗</span>
                    </x-ui.button>
                @endif

                <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}" class="inline">
                    @csrf
                    <x-ui.button 
                        type="submit" 
                        variant="{{ $product->is_active ? 'secondary' : 'primary' }}"
                        size="sm"
                        class="font-mono text-xs uppercase"
                    >
                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                    </x-ui.button>
                </form>

                @if($product->trashed())
                    <form method="POST" action="{{ route('admin.products.restore', $product) }}" class="inline">
                        @csrf
                        <x-ui.button 
                            type="submit" 
                            variant="secondary"
                            size="sm"
                            class="font-mono text-xs uppercase text-emerald-400 border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20"
                        >
                            Restore Product
                        </x-ui.button>
                    </form>
                @else
                    <x-ui.button 
                        type="button" 
                        variant="danger"
                        size="sm"
                        class="font-mono text-xs uppercase"
                        @click="archiveModalOpen = true"
                    >
                        Archive
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Sticky ActionBar with Save and Cancel -->
        <x-ui.action-bar class="sticky top-16 z-30 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono bg-zinc-900 border border-white/[0.08] text-zinc-300">
                    <span class="w-1.5 h-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-400' : 'bg-zinc-400' }}"></span>
                    <span class="truncate">Editing ID #{{ $product->id }}</span>
                </span>
                <span class="text-xs font-sans text-zinc-400 hidden md:inline truncate">{{ $product->name }}</span>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <x-ui.button 
                    :href="route('admin.products.index')" 
                    variant="secondary"
                    size="sm"
                    class="font-sans"
                >
                    Cancel
                </x-ui.button>
                <x-ui.button 
                    type="submit" 
                    form="product-form"
                    variant="primary"
                    size="sm"
                    class="font-sans"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Save Product Changes</span>
                </x-ui.button>
            </div>
        </x-ui.action-bar>


        <!-- Main Product Information & Pricing Form -->
        <form id="product-form" method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column (8 cols): Basics -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- GlassCard 1: Basics -->
                    <x-ui.glass-card title="Basics" description="Product identity, classification taxonomy, and descriptive narrative.">
                        <div class="space-y-4">
                            <!-- Product Name -->
                            <x-ui.form-field 
                                label="Product Name" 
                                name="name" 
                                :required="true"
                                id="field-name"
                            >
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="field-name-input"
                                    value="{{ old('name', $product->name) }}"
                                    required
                                    placeholder="e.g. Acid Wash Tactical Cargo Jacket"
                                    class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3.5 py-2.5 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                />
                            </x-ui.form-field>

                            <!-- URL Slug -->
                            <x-ui.form-field 
                                label="URL Slug" 
                                name="slug" 
                                :required="true"
                                id="field-slug"
                            >
                                <div class="flex items-center">
                                    <span class="px-3.5 py-2.5 rounded-l-xl bg-zinc-900 border border-r-0 border-white/[0.08] text-xs font-mono text-admin-muted select-none">
                                        /product/
                                    </span>
                                    <input 
                                        type="text" 
                                        name="slug" 
                                        id="field-slug-input"
                                        value="{{ old('slug', $product->slug) }}"
                                        required
                                        placeholder="acid-wash-tactical-cargo-jacket"
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-r-xl px-3.5 py-2.5 text-xs font-mono text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    />
                                </div>
                            </x-ui.form-field>

                            <!-- Category & Collection (2 columns) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Category -->
                                <x-ui.form-field 
                                    label="Category" 
                                    name="category_id" 
                                    :required="true"
                                    id="field-category-id"
                                >
                                    <select 
                                        name="category_id" 
                                        id="field-category-id-input"
                                        required
                                        class="w-full py-2.5 px-3 text-xs rounded-xl bg-zinc-950/80 border border-white/[0.08] text-zinc-200 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-colors font-sans"
                                    >
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ (string) old('category_id', $product->category_id) === (string) $category->id ? 'selected' : '' }} class="bg-zinc-900 text-zinc-200">
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-ui.form-field>

                                <!-- Collection Name -->
                                <x-ui.form-field 
                                    label="Collection Name" 
                                    name="collection_name" 
                                    id="field-collection-name"
                                >
                                    <input 
                                        type="text" 
                                        name="collection_name" 
                                        id="field-collection-name-input"
                                        value="{{ old('collection_name', $product->collection_name) }}"
                                        placeholder="e.g. Y2K Street, Cyber Goth"
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3.5 py-2.5 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    />
                                </x-ui.form-field>
                            </div>

                            <!-- Description & Size Guide (2 columns on sm+) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Description -->
                                <x-ui.form-field 
                                    label="Description" 
                                    name="description" 
                                    id="field-description"
                                >
                                    <textarea 
                                        name="description" 
                                        id="field-description-input"
                                        rows="4" 
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    >{{ old('description', $product->description) }}</textarea>
                                </x-ui.form-field>

                                <!-- Size Guide -->
                                <x-ui.form-field 
                                    label="Size Guide & Measurements" 
                                    name="size_guide" 
                                    id="field-size-guide"
                                >
                                    <textarea 
                                        name="size_guide" 
                                        id="field-size-guide-input"
                                        rows="4" 
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    >{{ old('size_guide', $product->size_guide) }}</textarea>
                                </x-ui.form-field>
                            </div>
                        </div>
                    </x-ui.glass-card>
                </div>

                <!-- Right Column (4 cols): Pricing & Status/Visibility -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- GlassCard 2: Pricing -->
                    <x-ui.glass-card title="Pricing" description="Retail listing price and optional compare-at strike tier.">
                        <div class="space-y-4">
                            <!-- Retail Price -->
                            <x-ui.form-field 
                                label="Retail Price ($ USD)" 
                                name="price" 
                                :required="true"
                                id="field-price"
                            >
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-admin-muted text-xs font-mono pointer-events-none">$</span>
                                    <input 
                                        type="number" 
                                        name="price" 
                                        id="field-price-input"
                                        step="0.01" 
                                        min="0.01" 
                                        max="99999.99" 
                                        value="{{ old('price', $product->price) }}"
                                        required
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl pl-7 pr-3 py-2.5 text-xs font-mono tabular-nums text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    />
                                </div>
                            </x-ui.form-field>

                            <!-- Compare At Price -->
                            <x-ui.form-field 
                                label="Compare-at Price ($ USD)" 
                                name="compare_at_price" 
                                helper="Must exceed retail price if provided."
                                id="field-compare-at-price"
                            >
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-admin-muted text-xs font-mono pointer-events-none">$</span>
                                    <input 
                                        type="number" 
                                        name="compare_at_price" 
                                        id="field-compare-at-price-input"
                                        step="0.01" 
                                        min="0.01" 
                                        max="99999.99" 
                                        value="{{ old('compare_at_price', $product->compare_at_price) }}"
                                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl pl-7 pr-3 py-2.5 text-xs font-mono tabular-nums text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                                    />
                                </div>
                            </x-ui.form-field>
                        </div>
                    </x-ui.glass-card>

                    <!-- GlassCard 3: Status/Visibility -->
                    <x-ui.glass-card title="Status/Visibility" description="Control publishing status and storefront promotional badges.">
                        <div class="space-y-3">
                            <!-- Active Listing -->
                            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16] cursor-pointer transition-all">
                                <input 
                                    type="checkbox" 
                                    name="is_active" 
                                    id="field-is-active-input"
                                    value="1" 
                                    {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-hoyos-red focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                />
                                <div class="min-w-0 select-none">
                                    <span class="text-xs font-semibold text-zinc-200 block font-sans">Active (Visible on Storefront)</span>
                                    <span class="text-[10px] text-zinc-400 block font-sans">Live in storefront catalog listings</span>
                                </div>
                            </label>

                            <!-- Featured Product -->
                            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16] cursor-pointer transition-all">
                                <input 
                                    type="checkbox" 
                                    name="is_featured" 
                                    id="field-is-featured-input"
                                    value="1" 
                                    {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                />
                                <div class="min-w-0 select-none">
                                    <span class="text-xs font-semibold text-zinc-200 block font-sans">Featured Product</span>
                                    <span class="text-[10px] text-zinc-400 block font-sans">Highlighted in curated hero sections</span>
                                </div>
                            </label>

                            <!-- New Arrival Tag -->
                            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16] cursor-pointer transition-all">
                                <input 
                                    type="checkbox" 
                                    name="is_new_arrival" 
                                    id="field-is-new-arrival-input"
                                    value="1" 
                                    {{ old('is_new_arrival', $product->is_new_arrival) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-hoyos-red focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                />
                                <div class="min-w-0 select-none">
                                    <span class="text-xs font-semibold text-zinc-200 block font-sans">New Arrival Tag</span>
                                    <span class="text-[10px] text-zinc-400 block font-sans">Badged with vibrant NEW pill</span>
                                </div>
                            </label>
                        </div>
                    </x-ui.glass-card>

                    <!-- Save Trigger Card -->
                    <x-ui.glass-card variant="subtle" class="p-4 space-y-3">
                        <x-ui.button 
                            type="submit" 
                            variant="primary"
                            size="md"
                            class="w-full font-sans font-bold"
                        >
                            Save Product Changes
                        </x-ui.button>
                        <p class="text-[10px] font-sans text-admin-muted text-center">
                            Audit trail records change snapshots without altering historical order records.
                        </p>
                    </x-ui.glass-card>
                </div>
            </div>
        </form>

        <!-- GlassCard 4: Variants & Sizes -->
        <x-ui.glass-card title="Variants & Sizes" description="Active sizing matrix, inventory SKU taxonomy, and price adjustments.">
            <x-slot:header>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-white/[0.08]">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-red font-bold block">
                            SKU & Sizing Variants
                        </span>
                        <h2 class="text-base font-display text-white uppercase tracking-wider">
                            Active Sizes ({{ $product->variants->whereNull('deleted_at')->count() }})
                        </h2>
                    </div>
                    <div class="text-[11px] font-sans text-zinc-400">
                        Stock adjustments are managed via the Inventory module
                    </div>
                </div>
            </x-slot:header>

            <div class="space-y-6">
                <!-- Existing Variants Table / Matrix (Usable at 1280px & 390px) -->
                @if($product->variants->isEmpty())
                    <div class="p-8 text-center text-xs text-zinc-400 border border-dashed border-white/[0.1] rounded-2xl bg-white/[0.01]">
                        No variants created yet. Add at least one size below.
                    </div>
                @else
                    <div class="overflow-x-auto -mx-5 sm:mx-0 px-5 sm:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[600px] sm:min-w-full">
                            <thead>
                                <tr class="border-b border-white/[0.08] text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                                    <th class="py-3 px-3 font-mono">Size</th>
                                    <th class="py-3 px-3 font-mono">SKU</th>
                                    <th class="py-3 px-3 text-right font-mono">Price Adj ($)</th>
                                    <th class="py-3 px-3 text-right font-mono">Effective Price</th>
                                    <th class="py-3 px-3 text-center font-mono">Stock</th>
                                    <th class="py-3 px-3 text-center font-mono">State</th>
                                    <th class="py-3 px-3 text-right font-sans">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @foreach($product->variants as $variant)
                                    @php
                                        $isVarTrashed = $variant->trashed();
                                    @endphp
                                    <tr class="hover:bg-white/[0.02] transition-colors {{ $isVarTrashed ? 'opacity-50 bg-red-950/10' : '' }}">
                                        <!-- Size -->
                                        <td class="py-3 px-3 font-mono font-bold text-white text-sm">
                                            {{ $variant->size }}
                                        </td>

                                        <!-- SKU -->
                                        <td class="py-3 px-3 font-mono text-zinc-300">
                                            {{ $variant->sku }}
                                        </td>

                                        <!-- Price Adjustment -->
                                        <td class="py-3 px-3 text-right font-mono tabular-nums text-zinc-400">
                                            {{ $variant->price_adjustment >= 0 ? '+' : '' }}${{ number_format($variant->price_adjustment, 2) }}
                                        </td>

                                        <!-- Effective Price -->
                                        <td class="py-3 px-3 text-right font-mono tabular-nums font-bold text-white">
                                            ${{ number_format($variant->effective_price, 2) }}
                                        </td>

                                        <!-- Stock (Link to Inventory) -->
                                        <td class="py-3 px-3 text-center">
                                            <a 
                                                href="{{ route('admin.inventory.show', $variant) }}" 
                                                class="inline-flex items-center gap-1 font-mono font-bold px-2 py-0.5 rounded text-[11px] whitespace-nowrap {{ $variant->stock_quantity <= 0 ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' }} hover:underline"
                                                title="View stock history & adjust"
                                            >
                                                <span>{{ $variant->stock_quantity }} units</span>
                                                <span class="text-[9px]">↗</span>
                                            </a>
                                        </td>

                                        <!-- State -->
                                        <td class="py-3 px-3 text-center">
                                            @if($isVarTrashed)
                                                <span class="px-2 py-0.5 rounded text-[9px] font-mono uppercase bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                                    Archived
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[9px] font-mono uppercase bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                                    Active
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3 px-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if($isVarTrashed)
                                                    <form method="POST" action="{{ route('admin.variants.restore', $variant) }}" class="inline">
                                                        @csrf
                                                        <x-ui.button 
                                                            type="submit" 
                                                            variant="secondary"
                                                            size="xs"
                                                            class="font-mono uppercase text-emerald-400 border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20"
                                                        >
                                                            Restore Size
                                                        </x-ui.button>
                                                    </form>
                                                @else
                                                    <x-ui.button 
                                                        type="button" 
                                                        variant="secondary"
                                                        size="xs"
                                                        class="font-mono text-zinc-300 hover:text-white"
                                                        @click="openEditVariant({ id: {{ $variant->id }}, size: '{{ $variant->size }}', sku: '{{ $variant->sku }}', price_adjustment: {{ $variant->price_adjustment }} })"
                                                    >
                                                        Edit SKU
                                                    </x-ui.button>

                                                    <form method="POST" action="{{ route('admin.variants.destroy', $variant) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-ui.button 
                                                            type="submit" 
                                                            variant="ghost"
                                                            size="xs"
                                                            class="font-mono text-rose-400 hover:text-rose-300 hover:bg-rose-500/10"
                                                            onclick="return confirm('Soft-delete size variant {{ $variant->size }}?')"
                                                        >
                                                            Remove
                                                        </x-ui.button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Add New Variant Form (Usable at 1280px and 390px) -->
                <div class="p-4 sm:p-5 bg-zinc-950/80 rounded-2xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-white uppercase tracking-wider block font-sans">
                            Add Size Variant
                        </span>
                        <span class="text-[10px] font-mono text-zinc-400">Inventory Sizing Record</span>
                    </div>

                    <form method="POST" action="{{ route('admin.variants.store', $product) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                        @csrf

                        <!-- Size -->
                        <x-ui.form-field label="Size" name="size" :required="true" id="field-variant-size">
                            <select 
                                name="size" 
                                required
                                class="w-full bg-zinc-900 border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none"
                            >
                                <option value="" class="bg-zinc-900 text-zinc-400">Select Size</option>
                                @foreach($allowedSizes as $s)
                                    <option value="{{ $s }}" {{ old('size') === $s ? 'selected' : '' }} class="bg-zinc-900 text-zinc-200">{{ $s }}</option>
                                @endforeach
                            </select>
                        </x-ui.form-field>

                        <!-- SKU -->
                        <x-ui.form-field label="SKU" name="sku" :required="true" id="field-variant-sku">
                            <input 
                                type="text" 
                                name="sku" 
                                required
                                value="{{ old('sku') }}"
                                placeholder="e.g. HOYOS-{{ strtoupper(substr($product->slug, 0, 3)) }}-M"
                                class="w-full bg-zinc-900 border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-mono text-white placeholder:text-admin-muted focus:outline-none"
                            />
                        </x-ui.form-field>

                        <!-- Price Adj ($) -->
                        <x-ui.form-field label="Price Adj ($)" name="price_adjustment" id="field-variant-price-adj">
                            <input 
                                type="number" 
                                name="price_adjustment" 
                                step="0.01" 
                                value="{{ old('price_adjustment', '0.00') }}"
                                placeholder="0.00"
                                class="w-full bg-zinc-900 border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-mono tabular-nums text-white placeholder:text-admin-muted focus:outline-none"
                            />
                        </x-ui.form-field>

                        <!-- Initial Stock -->
                        <x-ui.form-field label="Initial Stock" name="initial_stock" id="field-variant-stock">
                            <input 
                                type="number" 
                                name="initial_stock" 
                                min="0" 
                                value="{{ old('initial_stock', '0') }}"
                                placeholder="0"
                                class="w-full bg-zinc-900 border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-mono tabular-nums text-white placeholder:text-admin-muted focus:outline-none"
                            />
                        </x-ui.form-field>

                        <!-- Submit Button -->
                        <div>
                            <x-ui.button 
                                type="submit" 
                                variant="primary"
                                size="sm"
                                class="w-full font-mono uppercase font-bold py-2"
                            >
                                Add Size
                            </x-ui.button>
                        </div>
                    </form>
                    <span class="text-[10px] text-zinc-400 font-sans block leading-normal">
                        Setting initial stock > 0 automatically creates a stock adjustment audit log with type <code>manual_restock</code>.
                    </span>
                </div>
            </div>
        </x-ui.glass-card>

        <!-- GlassCard 5: Images -->
        <x-ui.glass-card title="Images" description="Media photography, storefront lookbook gallery, and thumbnail sorting.">
            <x-slot:header>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-white/[0.08]">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-red font-bold block">
                            Media & Lookbook Photography
                        </span>
                        <h2 class="text-base font-display text-white uppercase tracking-wider">
                            Product Gallery ({{ $product->images->count() }})
                        </h2>
                    </div>
                    <div class="text-[11px] font-sans text-zinc-400">
                        Exactly one primary image required. Formats: JPG, PNG, WebP (max 4MB).
                    </div>
                </div>
            </x-slot:header>

            <div class="space-y-6">
                <!-- Existing Images Grid -->
                @if($product->images->isEmpty())
                    <div class="p-8 text-center text-xs text-zinc-400 border border-dashed border-white/[0.1] rounded-2xl bg-white/[0.01]">
                        No images uploaded yet. Upload front lookbook photos below.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($product->images as $image)
                            <div class="bg-zinc-950/80 rounded-2xl border border-white/[0.08] overflow-hidden flex flex-col justify-between group hover:border-white/[0.18] transition-all">
                                <!-- Image Preview -->
                                <div class="relative aspect-[4/5] bg-black overflow-hidden">
                                    <img 
                                        src="{{ asset($image->image_path) }}" 
                                        alt="Product image #{{ $image->id }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    />

                                    @if($image->is_primary)
                                        <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase bg-hoyos-red text-white shadow-[0_0_10px_#FF002E]">
                                            Primary
                                        </span>
                                    @endif

                                    <div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded-md text-[9px] font-mono bg-black/85 text-white border border-white/10">
                                        Order: {{ $image->display_order }}
                                    </div>
                                </div>

                                <!-- Meta Edit & Controls -->
                                <div class="p-3.5 space-y-2.5 text-xs">
                                    <form method="POST" action="{{ route('admin.images.update', $image) }}" class="space-y-2">
                                        @csrf
                                        @method('PUT')

                                        <x-ui.form-field label="Credit" name="credit">
                                            <input 
                                                type="text" 
                                                name="credit" 
                                                value="{{ old('credit', $image->credit) }}" 
                                                placeholder="Photographer / Model"
                                                class="w-full bg-zinc-900 border border-white/[0.08] rounded-lg px-2.5 py-1.5 text-[11px] font-sans text-white focus:outline-none focus:border-hoyos-red"
                                            />
                                        </x-ui.form-field>

                                        <x-ui.form-field label="Source URL" name="source_url">
                                            <input 
                                                type="url" 
                                                name="source_url" 
                                                value="{{ old('source_url', $image->source_url) }}" 
                                                placeholder="https://..."
                                                class="w-full bg-zinc-900 border border-white/[0.08] rounded-lg px-2.5 py-1.5 text-[11px] font-mono text-white focus:outline-none focus:border-hoyos-red"
                                            />
                                        </x-ui.form-field>

                                        <div class="flex items-center gap-1.5 pt-1">
                                            <div class="w-16">
                                                <input 
                                                    type="number" 
                                                    name="display_order" 
                                                    value="{{ $image->display_order }}" 
                                                    class="w-full bg-zinc-900 border border-white/[0.08] rounded-lg px-2 py-1.5 text-[11px] font-mono text-center text-white focus:outline-none focus:border-hoyos-red"
                                                    title="Display sequence"
                                                />
                                            </div>
                                            <x-ui.button 
                                                type="submit" 
                                                variant="secondary"
                                                size="xs"
                                                class="flex-1 font-mono text-[10px]"
                                            >
                                                Save Meta
                                            </x-ui.button>
                                        </div>
                                    </form>

                                    <div class="flex items-center justify-between pt-2.5 border-t border-white/[0.08]">
                                        @if(!$image->is_primary)
                                            <form method="POST" action="{{ route('admin.images.set-primary', $image) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="text-[10px] font-mono text-hoyos-red hover:underline uppercase font-bold cursor-pointer"
                                                >
                                                    Set Primary
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] font-mono text-emerald-400 font-semibold">★ Primary</span>
                                        @endif

                                        <form method="POST" action="{{ route('admin.images.destroy', $image) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                onclick="return confirm('Delete this image? Physical file will also be removed.')"
                                                class="text-[10px] font-mono text-rose-400 hover:text-rose-300 cursor-pointer"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Upload New Photos Form (Restyled dropzone, keeps input names and upload handling) -->
                <div class="p-4 sm:p-5 bg-zinc-950/80 rounded-2xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-white uppercase tracking-wider block font-sans">
                            Upload Photos to Product Lookbook
                        </span>
                        <span class="text-[10px] font-mono text-zinc-400">JPG, PNG, WEBP (Max 4MB)</span>
                    </div>

                    <form method="POST" action="{{ route('admin.images.store', $product) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Dropzone Area -->
                        <div class="relative group rounded-2xl border-2 border-dashed border-white/[0.12] hover:border-hoyos-red/60 transition-colors bg-white/[0.01] hover:bg-white/[0.02] p-6 text-center">
                            <input 
                                type="file" 
                                name="images[]" 
                                id="product-images-input"
                                multiple
                                required
                                accept="image/jpeg,image/png,image/webp"
                                @change="handleFiles($event)"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                            />
                            <div class="space-y-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-xl bg-white/[0.04] border border-white/[0.08] mx-auto flex items-center justify-center text-zinc-400 group-hover:text-hoyos-red group-hover:border-hoyos-red/40 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div class="text-xs font-sans text-zinc-200 font-medium">
                                    <span class="text-hoyos-red underline">Click to choose files</span> or drag and drop lookbook photos
                                </div>
                                <p class="text-[11px] font-sans text-admin-muted">
                                    Hold Shift or Ctrl to select multiple high-resolution images
                                </p>
                            </div>

                            <!-- Live preview of selected file count -->
                            <div x-show="selectedUploadCount > 0" x-cloak class="mt-3 pt-3 border-t border-white/[0.08] text-xs font-mono text-emerald-400 flex items-center justify-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span x-text="selectedUploadCount + ' files selected for upload'"></span>
                            </div>
                        </div>

                        @error('images')
                            <p class="text-[11px] text-hoyos-red font-sans">{{ $message }}</p>
                        @enderror
                        @error('images.*')
                            <p class="text-[11px] text-hoyos-red font-sans">{{ $message }}</p>
                        @enderror

                        <!-- Meta row for newly uploaded photos -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <x-ui.form-field label="Photo Credit" name="credit" id="field-image-credit">
                                <input 
                                    type="text" 
                                    name="credit" 
                                    value="{{ old('credit') }}"
                                    placeholder="e.g. Model @chhaya_phnompenh"
                                    class="w-full bg-zinc-900 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-sans text-white placeholder:text-admin-muted focus:outline-none"
                                />
                            </x-ui.form-field>

                            <x-ui.form-field label="Source URL" name="source_url" id="field-image-source-url">
                                <input 
                                    type="url" 
                                    name="source_url" 
                                    value="{{ old('source_url') }}"
                                    placeholder="https://instagram.com/..."
                                    class="w-full bg-zinc-900 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3 py-2 text-xs font-mono text-white placeholder:text-admin-muted focus:outline-none"
                                />
                            </x-ui.form-field>

                            <div class="flex items-center pb-2">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        name="is_primary" 
                                        value="1" 
                                        {{ old('is_primary') ? 'checked' : '' }}
                                        class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-hoyos-red focus:ring-0 cursor-pointer"
                                    />
                                    <span class="text-xs font-semibold text-white font-sans">Make first upload Primary</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-ui.button 
                                type="submit" 
                                variant="primary"
                                size="sm"
                                class="font-mono uppercase font-bold"
                            >
                                Upload Images
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.glass-card>

        <!-- Edit Variant SKU & Price Modal (Uses shared x-ui.modal) -->
        <x-ui.modal
            name="editVariantModalOpen"
            title="Edit Variant"
            variant="primary"
            maxWidth="max-w-md"
        >
            <x-slot:header>
                <h3 class="text-sm font-mono font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Edit Variant: Size <strong x-text="activeVariant.size"></strong></span>
                </h3>
            </x-slot:header>

            <form :action="'{{ url('/admin/variants') }}/' + activeVariant.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <x-ui.form-field label="SKU" name="sku" :required="true">
                    <input 
                        type="text" 
                        name="sku" 
                        x-model="activeVariant.sku"
                        required
                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none"
                    />
                </x-ui.form-field>

                <x-ui.form-field label="Price Adjustment ($ USD)" name="price_adjustment" helper="Difference relative to base product price (${{ number_format($product->price, 2) }}).">
                    <input 
                        type="number" 
                        step="0.01"
                        name="price_adjustment" 
                        x-model="activeVariant.price_adjustment"
                        class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red rounded-xl px-3 py-2 text-xs font-mono tabular-nums text-white focus:outline-none"
                    />
                </x-ui.form-field>

                <div class="p-3 rounded-xl bg-zinc-950/60 text-[11px] font-sans text-zinc-400 border border-white/[0.06]">
                    Notice: Stock quantity cannot be edited here. Use the Inventory adjustments module.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-ui.button 
                        type="button" 
                        @click="editVariantModalOpen = false"
                        variant="ghost"
                        size="sm"
                        class="font-sans"
                    >
                        Cancel
                    </x-ui.button>
                    <x-ui.button 
                        type="submit" 
                        variant="primary"
                        size="sm"
                        class="font-mono uppercase font-bold"
                    >
                        Update Variant
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <!-- ConfirmModal for Archiving Products (Danger variant with Red Header Dot) -->
        <x-ui.confirm-modal
            name="archiveModalOpen"
            title="Archive Product"
            :action="route('admin.products.destroy', $product)"
            method="DELETE"
            confirmText="Archive Product"
            cancelText="Cancel"
            variant="danger"
        >
            <div class="p-3.5 bg-zinc-950/80 rounded-xl border border-white/[0.08] text-xs text-zinc-300 space-y-2 font-sans mb-2">
                <p class="text-zinc-200">
                    Are you sure you want to archive <strong class="text-white">{{ $product->name }}</strong>?
                </p>
                <p class="text-[11px] leading-relaxed text-zinc-400">
                    Archiving hides this product from the storefront. All historical orders, sales records, and variant inventory adjustments are preserved. You can restore it anytime from the Trashed filter.
                </p>
            </div>
        </x-ui.confirm-modal>

    </div>

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const errorSummary = document.getElementById('form-error-summary');
            if (errorSummary) {
                errorSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const firstLink = errorSummary.querySelector('a');
                if (firstLink && firstLink.getAttribute('href')) {
                    const targetField = document.querySelector(firstLink.getAttribute('href') + '-input') || document.querySelector(firstLink.getAttribute('href'));
                    if (targetField && targetField.focus) {
                        targetField.focus();
                    }
                }
            }
        });
    </script>
    @endif
</x-admin-layout>
