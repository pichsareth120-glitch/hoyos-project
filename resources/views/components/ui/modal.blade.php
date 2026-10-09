@props([
    'name' => 'modalOpen',
    'title' => 'Dialog',
    'id' => null,
    'maxWidth' => 'max-w-md',
    'variant' => 'neutral', // 'danger', 'destructive', 'primary', 'neutral'
])

@php
    $modalId = $id ?? ('modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) . '-' . uniqid());
    $isDanger = in_array($variant, ['danger', 'destructive']);
@endphp

<div 
    x-data="{
        triggerEl: null,
        init() {
            this.$watch('{{ $name }}', (isOpen) => {
                if (isOpen) {
                    this.triggerEl = document.activeElement;
                    this.$nextTick(() => {
                        this.focusInitial();
                    });
                } else if (this.triggerEl) {
                    this.$nextTick(() => {
                        if (this.triggerEl && typeof this.triggerEl.focus === 'function' && document.body.contains(this.triggerEl)) {
                            this.triggerEl.focus();
                        }
                    });
                }
            });
        },
        focusInitial() {
            if (!this.$refs.modalDialog) return;
            const focusables = this.getFocusables();
            if (focusables.length === 0) return;
            const firstInput = this.$refs.modalDialog.querySelector('input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled])');
            if (firstInput && !firstInput.readOnly) {
                firstInput.focus();
            } else {
                focusables[0].focus();
            }
        },
        getFocusables() {
            if (!this.$refs.modalDialog) return [];
            const elements = this.$refs.modalDialog.querySelectorAll(
                'button:not([disabled]), [href], input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\'-1\'])'
            );
            return Array.from(elements).filter(el => {
                return el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0;
            });
        },
        handleKeydown(e) {
            if (!{{ $name }}) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                {{ $name }} = false;
                return;
            }
            if (e.key === 'Tab') {
                const focusables = this.getFocusables();
                if (focusables.length === 0) {
                    e.preventDefault();
                    return;
                }
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (e.shiftKey) {
                    if (document.activeElement === first || !this.$refs.modalDialog.contains(document.activeElement)) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last || !this.$refs.modalDialog.contains(document.activeElement)) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
        }
    }"
    @keydown.window="handleKeydown($event)"
    x-show="{{ $name }}" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-all duration-200"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div 
        x-ref="modalDialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $modalId }}-title"
        tabindex="-1"
        @click.away="{{ $name }} = false"
        class="glass-elevated border border-white/15 rounded-2xl {{ $maxWidth }} w-full p-6 shadow-glass-elevated space-y-4 focus:outline-none"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
            @if(isset($header))
                <div id="{{ $modalId }}-title" class="flex items-center gap-2">
                    {{ $header }}
                </div>
            @else
                <h3 id="{{ $modalId }}-title" class="text-sm font-mono font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $isDanger ? 'bg-hoyos-red shadow-[0_0_8px_#FF002E]' : 'bg-emerald-400' }}"></span>
                    <span>{{ $title }}</span>
                </h3>
            @endif
            <button 
                type="button" 
                @click="{{ $name }} = false" 
                class="text-zinc-400 hover:text-white transition-colors text-xs font-mono p-1 rounded focus:outline-none focus-visible:ring-1 focus-visible:ring-hoyos-red" 
                aria-label="Close dialog"
            >✕</button>
        </div>

        {{ $slot }}

        @if(isset($footer))
            <div class="pt-2">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
