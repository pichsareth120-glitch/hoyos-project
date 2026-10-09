# HOYOS Storefront UI Redesign — Dark Glassmorphism Design System

## 1. Executive Summary & Design Language

HOYOS is a Cambodian Y2K / goth / streetwear e-commerce brand. The storefront UI was redesigned with a **"Dark Glassmorphism" (Crimson Noir)** aesthetic inspired by `shadcn/ui` principles, translated into high-performance Laravel Blade components and Tailwind CSS.

### Aesthetic Pillars:
1. **Atmospheric Canvas:** Ultra-dark background (`#080103`) accented by multi-layered radial gradients emitting subtle deep crimson (`#FF002E`) and ruby glows.
2. **Layered Glassmorphic Surfaces:** Multi-stop translucency with hardware-accelerated backdrop blur (`16px` to `28px`), semi-transparent borders (`rgba(255, 255, 255, 0.08)` to `rgba(255, 0, 46, 0.45)`), and top highlight inset borders.
3. **Crimson Noir Accents:** High-energy crimson (`#FF002E`) utilized for active states, focus rings, progress milestones, primary CTAs, and glowing badges.
4. **Bilingual Typography & Spacing:** Integrated `Anton` for punchy condensed headlines, `Inter` for clean body text, `Space Mono` for waybills/order codes, and `Noto Sans Khmer` for Khmer script rendering.

---

## 2. Design Tokens & CSS Custom Properties

Defined in [`tailwind.config.js`](file:///d:/Hoyos-project/tailwind.config.js) and [`resources/css/app.css`](file:///d:/Hoyos-project/resources/css/app.css):

### Glass Surfaces & Borders
| Token / Variable | Value | Purpose |
| :--- | :--- | :--- |
| `--glass-fill` / `glass.surface` | `rgba(255, 255, 255, 0.035)` | Standard card/panel background |
| `--glass-fill-elevated` / `glass.elevated` | `rgba(255, 255, 255, 0.065)` | Modals, drawers, dropdown menus |
| `--glass-fill-subtle` | `rgba(255, 255, 255, 0.02)` | Table headers, secondary cards |
| `--glass-border` | `rgba(255, 255, 255, 0.08)` | Default borders |
| `--glass-border-strong` | `rgba(255, 255, 255, 0.16)` | Hovered cards, prominent inputs |
| `--glass-border-crimson` | `rgba(255, 0, 46, 0.45)` | Active selection, primary borders |

### Colors & Glows
| Name | Value | Purpose |
| :--- | :--- | :--- |
| `crimson.DEFAULT` / `hoyos-red` | `#FF002E` | Brand primary color |
| `crimson.hover` / `hoyos-redHover` | `#E00029` | Button hover states |
| `glow.primary` | `0 0 24px rgba(255, 0, 46, 0.35)` | Glowing buttons and active cards |
| `shadow-glass` | `0 8px 32px rgba(0, 0, 0, 0.45)` | Deep elevation drop shadow |
| `shadow-glass-elevated` | `0 16px 48px rgba(0, 0, 0, 0.65)` | Modals, flyouts, and drawers |

### Fallbacks & Accessibility
- **`@supports not (backdrop-filter: blur(1px))`:** Automatically falls back to high-contrast opaque dark colors (`#0E0205` / `#160408`) to ensure legibility on low-end or unsupported browsers.
- **`@media (prefers-reduced-motion: reduce)`:** Disables transitions and animations (`animation: none !important; transition: none !important;`) for users with motion sensitivity.

---

## 3. Core UI Utility Classes

The following utility classes can be applied to any container or component:

```html
<!-- Base Glass Panel -->
<div class="glass p-6">
    Standard glass card with 16px backdrop blur, subtle highlight, and dark border.
</div>

<!-- Elevated Glass (Dropdowns, Drawers, Modals) -->
<div class="glass-elevated p-6">
    High-elevation glass with 24px backdrop blur and deeper shadow.
</div>

<!-- Interactive Clickable Card with Hover Lift -->
<div class="glass glass-interactive p-5">
    Elevates and highlights border on hover.
</div>

<!-- Selected / Active Card -->
<div class="glass glass-selected p-5">
    Glowing crimson border and subtle red background tint.
</div>

<!-- Glass Form Input -->
<input type="text" class="glass-input" />
```

---

## 4. Reusable Shadcn-Style Blade Components

Located in [`resources/views/components/ui/`](file:///d:/Hoyos-project/resources/views/components/ui):

### 1. Button (`<x-ui.button>`)
- **Variants:** `default` (crimson + glow), `secondary` (glass), `outline` (transparent + glass border), `ghost` (transparent), `destructive` (dark red).
- **Sizes:** `xs`, `sm`, `md`, `lg`, `icon`.
- **Props:** `loading` (shows animated spinner + disables button), `icon` (left icon slot).

```html
<x-ui.button variant="default" size="md">
    Proceed to Checkout
</x-ui.button>

<x-ui.button variant="secondary" size="sm">
    Cancel
</x-ui.button>
```

### 2. Badge (`<x-ui.badge>`)
- **Variants:** `paid` (emerald), `pending` (amber), `confirmed` (indigo), `cancelled` / `danger` (crimson), `default` (glass).
- **Props:** `pulse` (boolean for animated ping beacon).

```html
<x-ui.badge variant="paid" :pulse="true">
    Paid
</x-ui.badge>
```

### 3. Stepper (`<x-ui.stepper>`)
- Visual multi-step progress bar with numbered circles, completion checkmarks, and active step crimson glow.

```html
<x-ui.stepper 
    :current-step="$step" 
    :steps="[
        ['number' => 1, 'title' => 'Delivery Information', 'description' => 'Address & Contact'],
        ['number' => 2, 'title' => 'Payment Method', 'description' => 'KHQR or COD']
    ]" 
/>
```

### 4. Radio Card (`<x-ui.radio-card>`)
- Clickable glass selection card with radio check indicator and crimson border when active.

```html
<x-ui.radio-card 
    name="payment_method" 
    value="aba_khqr" 
    :selected="true"
    title="ABA KHQR"
    badge="Instant"
>
    Scan with ABA Mobile, Bakong, or any Cambodian banking app.
</x-ui.radio-card>
```

### 5. Input (`<x-ui.input>`)
- Glassmorphic text, email, tel, or password input with label, error message display, and toggle password visibility eye icon.

```html
<x-ui.input 
    type="password" 
    name="password" 
    label="New Password" 
    placeholder="Enter strong password"
    :show-password-toggle="true"
    :error="$errors->first('password')"
/>
```

### 6. Confirm Modal (`<x-ui.confirm-modal>`)
- Accessible Alpine.js modal with elevated glass styling, keyboard `Escape` dismissal, click-outside handling, and customizable confirm/cancel buttons.

---

## 5. Redesigned Pages & Enhancements

### 1. Layout & Header / Navigation ([`header.blade.php`](file:///d:/Hoyos-project/resources/views/components/header.blade.php))
- Floating glass pill navbar that dynamically increases blur and border intensity upon scrolling (`window.scrollY > 20`).
- User profile badge with online indicator dot and elevated glass dropdown.
- Fixed server-rendered initial cart count from `CartService` to eliminate client-side count popping.

### 2. Shopping Bag Drawer ([`cart-drawer.blade.php`](file:///d:/Hoyos-project/resources/views/components/cart-drawer.blade.php))
- High-elevation glass slide-over with dark backdrop blur.
- Quantity stepper controls (`[-] qty [+]`), instant trash remove button, and clear item count labeling (`X item(s)`).
- Sticky glass footer with subtotal, free shipping badge, and glowing crimson checkout button.

### 3. Account Hub ([`resources/views/account/index.blade.php`](file:///d:/Hoyos-project/resources/views/account/index.blade.php))
- Sticky glass sidebar with glowing active pills and item count chips. On mobile, automatically collapses into a horizontal scrollable pill bar.
- **Personal Information:** Structured inputs, explicit Day/Month/Year selectors, and unified crimson button.
- **Security & Password:** Password strength meter with real-time feedback (Weak/Fair/Good/Strong), visibility toggles, and unified crimson button.
- **Saved Addresses:** Active default address card with crimson glow and `DEFAULT` badge. Visual `DEMO DUPLICATE` badge on identical demo records. Accessible modal confirmation on address deletion.
- **My Orders:** Rich order cards with product thumbnails, monospace order numbers, semantic badges, and client-side status filter chips (`All`, `Paid`, `Pending`, `Delivered`).

### 4. 2-Step Checkout ([`resources/views/checkout/index.blade.php`](file:///d:/Hoyos-project/resources/views/checkout/index.blade.php))
- 2-Step visual stepper at the top.
- Step 1: Selectable saved address cards with radio indicator, or collapsible new address form.
- Step 2: Selectable payment method radio cards (ABA KHQR vs Cash on Delivery).
- Sticky glass Order Summary panel with product thumbnails, size labels, and highlighted totals.

### 5. Shipment Tracking ([`resources/views/orders/track.blade.php`](file:///d:/Hoyos-project/resources/views/orders/track.blade.php))
- Logistics hero card with carrier badge (`J&T Express`) and real-time fulfillment status.
- Waybill box with tracking number copy button (with toast confirmation) and direct link to official J&T Cambodia tracking.
- Glowing vertical milestone tracker with pulsing radar dot on current milestone and progress indicator line.
- Package contents card and delivery destination summary.

### 6. ABA KHQR Payment Screen ([`resources/views/payment/khqr.blade.php`](file:///d:/Hoyos-project/resources/views/payment/khqr.blade.php))
- **3-Step Progress Stepper:** Delivery (Completed ✓) → Payment (Active/Completed) → Confirmation (Upcoming/Active).
- **KHQR Standee Card:** Elevated dark glass frame (`.glass-elevated`) with vibrant crimson header.
- **Scannable Pure White Inner Card:** Intentional solid `#FFFFFF` container with zero blur, opacity, or tint to ensure mobile banking cameras can scan the QR code reliably.
- **Dedicated Confirmed State:** Solid white success screen with emerald checkmark, transaction approved badge, and redirecting indicator without layout bleed-through.
- **How to Pay Guide:** Numbered step chips with crimson accents, high contrast text (AA compliant), and bold bank names. Automatically dims to 30% opacity upon payment verification.
- **Developer Simulation Panel:** Glass card with `DEV MODE` badge and simulation button with loading spinner.
- **Order Details:** Glass card with clean label/value pairs (Recipient, Phone, Province, Items, Payable Total).

### 7. Order Confirmation & Receipt ([`resources/views/orders/show.blade.php`](file:///d:/Hoyos-project/resources/views/orders/show.blade.php))
- **Hero Card:** Elevated glass with soft emerald and crimson depth glows, animated success check with ping beacon, and payment status badge.
- **Order Reference Pill:** Monospace order number with copy-to-clipboard button and toast feedback.
- **Ordered Items:** Glass container with item thumbnails in glass tiles, size chips, and high-contrast unit/line pricing.
- **Shipping & Delivery Address:** Structured glass sub-panels for recipient, phone, city, courier partner, and delivery notes.
- **Financial Summary (Sticky):** Dynamic **"Total Paid"** (for paid orders) vs **"Total Due"** (for unpaid/COD orders). Includes payment method, payment status badge, and amber simulated demo banner.
- **Print Stylesheet (`@media print`):** Integrated "Print Receipt" button using `window.print()` that automatically converts the page to high-contrast black text on pure white paper, stripping headers, footers, toasts, and buttons.

### 8. Logistics & Delivery Order Lookup ([`resources/views/orders/lookup.blade.php`](file:///d:/Hoyos-project/resources/views/orders/lookup.blade.php))
- **Header:** Eyebrow `LOGISTICS & DELIVERY LOOKUP`, condensed bold title, and high-contrast AA subtitle.
- **Form Card:** Elevated glass container (`.glass-elevated`) with soft crimson depth glow aura (`bg-[#FF002E]/[0.14] blur-[100px]`).
- **Leading Icon Inputs:** `<x-ui.input>` with leading package and phone svg icons, uppercase auto-formatting, high-contrast placeholders (≥ 4.5:1), and contextual helper text.
- **State Handling & Security:** Danger glass alert for mismatched/missing orders without enumerating phone vs order number (`role="alert"`), plus inline field errors.
- **Role-Aware Context:** If authenticated, shows a gentle hint card with "Logged in as [User]" and a "My Orders →" direct link. If guest, replaces the tiny underlined link with a sleek ghost button link.
- **Assistance Information:** Embedded support card with official support email, hotline, and informative J&T waybill dispatch note.
- **Double-Submit Guard:** Alpine.js `submitting` state with loading spinner and disabled submit button.

---

## 6. Bugs Found & Handled

1. **Cart Count Inconsistency in Header vs Drawer:**
   - *Issue:* Header previously initialized `cartCount: 0` before an asynchronous client fetch, causing a visible flash from `0` to `2`. When a cart had 1 item with quantity 2, the drawer header read "1 Items" while the header badge showed "2".
   - *Fix:* Injected `$initialCartCount = app(\App\Services\CartService::class)->getCart()->total_quantity` in `header.blade.php` for instantaneous server render. In `cart-drawer.blade.php`, explicitly labeled count as `itemsCount + (itemsCount === 1 ? ' item' : ' items')`.
2. **Duplicate Saved Addresses in Demo Database:**
   - *Issue:* The demo database contains 3 identical addresses for `customer@hoyos.com`.
   - *Fix (UI-only):* Implemented a non-destructive fingerprint hashing check at the Blade view level to attach a subtle `DEMO DUPLICATE` badge on duplicate entries while keeping the database records intact.
3. **Button Style Inconsistencies:**
   - *Issue:* "Update Password" button was plain white while other primary actions were crimson.
   - *Fix:* Unified all form submit and primary action buttons to `<x-ui.button variant="default">` with `#FF002E` and crimson glow.
4. **Ambiguous "Total Paid / Due" Label on Order Receipt:**
   - *Issue:* Original view rendered a static ambiguous label "Total Paid / Due".
   - *Fix:* Conditioned label based on `$order->payment_status === 'paid'` to cleanly state "Total Paid" or "Total Due".
5. **QR Code Clashing & Text Bleed in Payment Screen:**
   - *Issue:* Original payment card had text overlapping and clashing on payment success.
   - *Fix:* Implemented a clean, solid, unmounted transition for the success state and wrapped the QR standee in an elevated glass container with a dedicated scannable white inner card.
6. **Footer Contrast Seam / Light Band on Short Pages:**
   - *Issue:* Footer background previously showed a slight lighter band when displayed beneath short pages.
   - *Fix:* Enhanced footer with `bg-[#080103]/90 backdrop-blur-2xl border-t border-white/10` to seamlessly blend into the dark canvas.


