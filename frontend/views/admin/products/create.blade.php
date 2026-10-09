@extends('layouts.admin')

@section('title', 'Create Product')

@section('content')
<div 
    class="space-y-6 max-w-7xl mx-auto" 
    x-data="{
        name: '{{ addslashes(old('name', '')) }}',
        slug: '{{ addslashes(old('slug', '')) }}',
        slugCustom: {{ old('slug') ? 'true' : 'false' }},
        selectedSizes: {{ json_encode(old('initial_sizes', ['S', 'M', 'L', 'XL'])) }},
        skuPrefix: '{{ addslashes(old('sku_prefix', 'HOYOS-')) }}',
        isActive: {{ old('is_active', true) ? 'true' : 'false' }},
        isFeatured: {{ old('is_featured') ? 'true' : 'false' }},
        isNewArrival: {{ old('is_new_arrival') ? 'true' : 'false' }},
        updateSlug() {
            if (!this.slugCustom) {
                this.slug = this.name.toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        },
        toggleSize(sz) {
            if (this.selectedSizes.includes(sz)) {
                this.selectedSizes = this.selectedSizes.filter(s => s !== sz);
            } else {
                this.selectedSizes.push(sz);
            }
        }
    }"
>
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-white/[0.08]">
        <div>
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-xs font-mono text-zinc-400 hover:text-white transition-colors mb-2">
                <span>←</span>
                <span>Back to Products</span>
            </a>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                <span class="text-[11px] font-mono uppercase tracking-wider text-hoyos-red font-semibold">Catalog Architecture</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white font-display uppercase">Create New Product</h1>
            <p class="text-xs text-zinc-400 mt-0.5">Configure apparel specifications, pricing tier economics, sizing matrices, and storefront visibility.</p>
        </div>
    </div>

    <!-- Sticky ActionBar with Save and Cancel -->
    <x-ui.action-bar class="sticky top-16 z-30 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono bg-zinc-900 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="truncate">Drafting New Listing</span>
            </span>
            <span class="text-xs font-sans text-zinc-400 hidden md:inline truncate" x-text="name ? name : 'Untitled Product'">Untitled Product</span>
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
                <span>Save Product</span>
            </x-ui.button>
        </div>
    </x-ui.action-bar>


    <!-- Main Product Form -->
    <form id="product-form" method="POST" action="{{ route('admin.products.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        @csrf

        <!-- Left Column: 8 Cols (Basics, Variants & Sizes, Images) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- GlassCard 1: Basics -->
            <x-ui.glass-card title="Basics" description="Base identity, public storefront routing slug, and narrative description.">
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
                            x-model="name"
                            @input="updateSlug()"
                            required
                            value="{{ old('name') }}"
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
                                x-model="slug"
                                @input="slugCustom = true"
                                required
                                value="{{ old('slug') }}"
                                placeholder="acid-wash-tactical-cargo-jacket"
                                class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-r-xl px-3.5 py-2.5 text-xs font-mono text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                            />
                        </div>
                        <p class="text-[10px] text-admin-muted font-mono mt-1 flex items-center gap-1">
                            <span>Storefront link:</span>
                            <span class="text-zinc-300" x-text="'/product/' + (slug || 'acid-wash-tactical-cargo-jacket')">/product/acid-wash-tactical-cargo-jacket</span>
                        </p>
                    </x-ui.form-field>

                    <!-- Category & Collection Name (2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Category Select -->
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
                                <option value="" class="bg-zinc-900 text-zinc-400">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }} class="bg-zinc-900 text-zinc-200">
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
                                value="{{ old('collection_name') }}"
                                placeholder="e.g. Y2K Chaos, Neo Matrix"
                                class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3.5 py-2.5 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                            />
                        </x-ui.form-field>
                    </div>

                    <!-- Description / Narrative Story -->
                    <x-ui.form-field 
                        label="Description / Narrative Story" 
                        name="description" 
                        id="field-description"
                    >
                        <textarea 
                            name="description" 
                            id="field-description-input"
                            rows="4" 
                            placeholder="Detailed garment specifications, fabric GSM, cut details, hardware accents, and aesthetic inspirations..."
                            class="w-full bg-zinc-950/80 border border-white/[0.08] text-zinc-100 rounded-xl p-3 text-xs font-sans focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-colors placeholder:text-admin-muted"
                        >{{ old('description') }}</textarea>
                    </x-ui.form-field>
                </div>
            </x-ui.glass-card>

            <!-- GlassCard 2: Variants & Sizes -->
            <x-ui.glass-card title="Variants & Sizes" description="Configure visual size pills and SKU generation taxonomy for apparel inventory.">
                <div class="space-y-5">
                    <!-- Visual Size Selection Pills -->
                    <div id="field-initial-sizes">
                        <label class="block text-[11px] font-mono uppercase font-semibold tracking-wider text-zinc-300 mb-2">
                            Available Sizing Matrix
                        </label>
                        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                            @php
                                $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                            @endphp
                            @foreach($sizes as $size)
                                <label 
                                    @click="toggleSize('{{ $size }}')"
                                    :class="selectedSizes.includes('{{ $size }}') 
                                        ? 'bg-hoyos-red/15 border-hoyos-red text-white shadow-sm ring-1 ring-hoyos-red/40' 
                                        : 'bg-zinc-950/80 border-white/[0.08] text-zinc-400 hover:border-white/[0.18] hover:text-zinc-200'"
                                    class="cursor-pointer border rounded-xl px-3.5 py-2 font-mono text-xs font-bold transition-all flex items-center gap-2 select-none"
                                >
                                    <input 
                                        type="checkbox" 
                                        name="initial_sizes[]" 
                                        value="{{ $size }}" 
                                        :checked="selectedSizes.includes('{{ $size }}')"
                                        class="sr-only"
                                    />
                                    <span>{{ $size }}</span>
                                    <span 
                                        x-show="selectedSizes.includes('{{ $size }}')"
                                        class="w-1.5 h-1.5 rounded-full bg-hoyos-red shadow-[0_0_6px_#FF002E]"
                                    ></span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-zinc-400 font-sans mt-2">
                            Select sizing variants to prepare initial inventory records.
                        </p>
                    </div>

                    <!-- SKU Prefix Input -->
                    <x-ui.form-field 
                        label="SKU Prefix" 
                        name="sku_prefix" 
                        id="field-sku-prefix"
                    >
                        <input 
                            type="text" 
                            name="sku_prefix" 
                            id="field-sku-prefix-input"
                            x-model="skuPrefix"
                            value="{{ old('sku_prefix', 'HOYOS-') }}"
                            placeholder="e.g. HOYOS-JKT-"
                            class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3.5 py-2.5 text-xs font-mono text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                        />
                        <div class="mt-2 p-2.5 rounded-xl bg-zinc-950/60 border border-white/[0.06] flex items-center justify-between text-[11px] font-mono text-zinc-400">
                            <span>Sample generated SKU:</span>
                            <span class="text-white font-mono font-bold" x-text="(skuPrefix || 'HOYOS-') + (selectedSizes[0] || 'M') + '-001'">HOYOS-M-001</span>
                        </div>
                    </x-ui.form-field>

                    <!-- Size & Fit Guide -->
                    <x-ui.form-field 
                        label="Size & Fit Guide" 
                        name="size_guide" 
                        id="field-size-guide"
                    >
                        <textarea 
                            name="size_guide" 
                            id="field-size-guide-input"
                            rows="2" 
                            placeholder="e.g. Boxy oversized streetwear silhouette. Model is 185cm wearing size L..."
                            class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                        >{{ old('size_guide') }}</textarea>
                    </x-ui.form-field>
                </div>
            </x-ui.glass-card>

            <!-- GlassCard 3: Images -->
            <x-ui.glass-card title="Images" description="Product lookbook photography and storefront gallery.">
                <div class="p-6 rounded-xl border border-dashed border-white/[0.12] bg-white/[0.02] text-center space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-white/[0.04] border border-white/[0.08] mx-auto flex items-center justify-center text-zinc-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="text-xs font-sans text-zinc-200 font-medium">
                        Image Upload Unlocks Upon Creation
                    </div>
                    <p class="text-[11px] font-sans text-zinc-400 max-w-md mx-auto leading-relaxed">
                        After saving the basic product details, you will be redirected to the Edit screen where you can upload lookbook photography, manage display sequencing, and set the primary thumbnail.
                    </p>
                </div>
            </x-ui.glass-card>

        </div>

        <!-- Right Column: 4 Cols (Pricing, Status/Visibility, Quick Actions) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- GlassCard 4: Pricing -->
            <x-ui.glass-card title="Pricing" description="Retail listing price and optional compare-at strike tier.">
                <div class="space-y-4">
                    <!-- Retail Price -->
                    <x-ui.form-field 
                        label="Retail Price ($)" 
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
                                value="{{ old('price') }}"
                                required
                                placeholder="45.00"
                                class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl pl-7 pr-3 py-2.5 text-xs font-mono tabular-nums text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                            />
                        </div>
                    </x-ui.form-field>

                    <!-- Compare-at Price -->
                    <x-ui.form-field 
                        label="Compare-at ($)" 
                        name="compare_at_price" 
                        helper="Optional strikethrough price. Must exceed retail price."
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
                                value="{{ old('compare_at_price') }}"
                                placeholder="60.00"
                                class="w-full bg-zinc-950/80 border border-white/[0.08] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl pl-7 pr-3 py-2.5 text-xs font-mono tabular-nums text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-colors"
                            />
                        </div>
                    </x-ui.form-field>
                </div>
            </x-ui.glass-card>

            <!-- GlassCard 5: Status/Visibility -->
            <x-ui.glass-card title="Status/Visibility" description="Control publishing status and storefront promotional flags.">
                <div class="space-y-3">
                    <!-- Active Listing -->
                    <label 
                        @click="isActive = !isActive"
                        :class="isActive ? 'border-hoyos-red/60 bg-hoyos-red/[0.06]' : 'border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16]'"
                        class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                    >
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            id="field-is-active-input"
                            value="1" 
                            :checked="isActive"
                            class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-hoyos-red focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                        <div class="min-w-0 select-none">
                            <span class="text-xs font-semibold text-zinc-200 block font-sans">Active Listing</span>
                            <span class="text-[10px] text-zinc-400 block font-sans">Publish immediately on storefront catalog</span>
                        </div>
                    </label>

                    <!-- Featured Product -->
                    <label 
                        @click="isFeatured = !isFeatured"
                        :class="isFeatured ? 'border-amber-500/60 bg-amber-500/[0.06]' : 'border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16]'"
                        class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                    >
                        <input 
                            type="checkbox" 
                            name="is_featured" 
                            id="field-is-featured-input"
                            value="1" 
                            :checked="isFeatured"
                            class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                        <div class="min-w-0 select-none">
                            <span class="text-xs font-semibold text-zinc-200 block font-sans">Featured Product</span>
                            <span class="text-[10px] text-zinc-400 block font-sans">Feature in curated storefront collections</span>
                        </div>
                    </label>

                    <!-- New Arrival -->
                    <label 
                        @click="isNewArrival = !isNewArrival"
                        :class="isNewArrival ? 'border-hoyos-red/60 bg-hoyos-red/[0.06]' : 'border-white/[0.08] bg-zinc-950/80 hover:border-white/[0.16]'"
                        class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                    >
                        <input 
                            type="checkbox" 
                            name="is_new_arrival" 
                            id="field-is-new-arrival-input"
                            value="1" 
                            :checked="isNewArrival"
                            class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-hoyos-red focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                        <div class="min-w-0 select-none">
                            <span class="text-xs font-semibold text-zinc-200 block font-sans">New Arrival</span>
                            <span class="text-[10px] text-zinc-400 block font-sans">Attach high-priority 'NEW' badge</span>
                        </div>
                    </label>
                </div>
            </x-ui.glass-card>

            <!-- Bottom Save / Cancel Card -->
            <x-ui.glass-card variant="subtle" class="p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs text-zinc-400 font-sans">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Changes take effect immediately on live storefront if marked Active.</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.button 
                        :href="route('admin.products.index')" 
                        variant="secondary"
                        size="sm"
                        class="w-1/2 font-sans"
                    >
                        Cancel
                    </x-ui.button>
                    <x-ui.button 
                        type="submit" 
                        variant="primary"
                        size="sm"
                        class="w-1/2 font-sans"
                    >
                        Save Product
                    </x-ui.button>
                </div>
            </x-ui.glass-card>

        </div>
    </form>
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
@endsection
