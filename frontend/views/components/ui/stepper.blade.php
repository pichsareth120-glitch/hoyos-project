@props([
    'currentStep' => 1,
    'interactive' => true,
    'steps' => [
        ['number' => 1, 'title' => 'Delivery Information', 'description' => 'Address & Contact'],
        ['number' => 2, 'title' => 'Payment Method', 'description' => 'KHQR or COD'],
    ],
])

<div {{ $attributes->merge(['class' => 'w-full glass p-3 sm:p-4 rounded-2xl flex items-center justify-between gap-2 sm:gap-4 mb-8']) }}>
    @foreach($steps as $index => $s)
        @php
            $stepNum = $s['number'] ?? ($index + 1);
        @endphp
        <div 
            class="flex-1 flex items-center gap-3 p-2 rounded-xl transition-all duration-200 {{ $interactive ? 'cursor-pointer hover:bg-white/[0.03]' : 'cursor-default' }}"
            @if($interactive)
                @click="step = {{ $stepNum }}"
            @endif
            :class="step === {{ $stepNum }} ? 'bg-white/[0.06] border border-white/15' : ''"
        >
            <!-- Step Circle Indicator -->
            <div 
                class="w-8 h-8 rounded-full flex items-center justify-center font-mono font-bold text-xs shrink-0 transition-all duration-200"
                :class="{
                    'bg-hoyos-red text-white shadow-[0_0_16px_rgba(255,0,46,0.5)] ring-2 ring-hoyos-red/30': step === {{ $stepNum }},
                    'bg-emerald-500 text-white shadow-[0_0_12px_rgba(34,197,94,0.3)]': step > {{ $stepNum }},
                    'bg-white/[0.06] text-white/40 border border-white/10': step < {{ $stepNum }}
                }"
            >
                <!-- Checkmark when completed -->
                <template x-if="step > {{ $stepNum }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <!-- Number when active or upcoming -->
                <template x-if="step <= {{ $stepNum }}">
                    <span>{{ $stepNum }}</span>
                </template>
            </div>

            <!-- Step Titles -->
            <div class="min-w-0">
                <p 
                    class="text-xs font-mono font-bold uppercase tracking-wider truncate transition-colors"
                    :class="step === {{ $stepNum }} ? 'text-white' : (step > {{ $stepNum }} ? 'text-white/80' : 'text-white/40')"
                >
                    {{ $s['title'] }}
                </p>
                @if(isset($s['description']))
                    <p 
                        class="text-[10px] truncate hidden sm:block transition-colors"
                        :class="step === {{ $stepNum }} ? 'text-hoyos-red font-medium' : 'text-white/40'"
                    >
                        {{ $s['description'] }}
                    </p>
                @endif
            </div>
        </div>

        @if(!$loop->last)
            <div class="w-6 sm:w-10 h-[1px] bg-white/10 shrink-0 mx-1"></div>
        @endif
    @endforeach
</div>
