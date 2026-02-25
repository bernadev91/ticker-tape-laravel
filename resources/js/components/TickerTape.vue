<template>
    <section class="mt-6 border-t border-border pt-4">
        <header class="mb-3 flex items-center justify-between">
            <h2 class="text-[11px] font-semibold uppercase tracking-[0.3em] text-text-muted">
                Ticker tape
            </h2>

            <button
                type="button"
                class="rounded-full border border-danger/40 bg-danger/10 px-3 py-1 text-[10px] font-medium text-danger hover:bg-danger/20 disabled:opacity-40"
                :disabled="!calculations.length"
                @click="$emit('clear-all')"
            >
                Clear all
            </button>
        </header>

        <p v-if="!calculations.length" class="text-[11px] text-text-muted">
            No calculations yet. Your history will appear here.
        </p>

        <ul
            v-else
            class="max-h-64 space-y-2 overflow-y-auto pr-1"
        >
            <li
                v-for="calculation in calculations"
                :key="calculation.id"
                class="flex items-start justify-between gap-3 rounded-lg border border-border bg-surface px-3 py-2 text-[11px]"
            >
                <div class="min-w-0 flex-1">
                    <div class="truncate font-mono text-text">
                        {{ calculation.expression }}
                    </div>

                    <div
                        v-if="calculation.had_error"
                        class="mt-0.5 text-danger"
                    >
                        {{ calculation.error_message || 'Error' }}
                    </div>

                    <div
                        v-else
                        class="mt-0.5 text-success"
                    >
                        = {{ calculation.result }}
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-0.5 shrink-0 rounded-full border border-border bg-surface p-1 text-text-muted hover:border-danger hover:bg-surface hover:text-danger"
                    @click="$emit('delete', calculation.id)"
                    aria-label="Delete calculation"
                >
                    <span class="block h-3 w-3 leading-none">×</span>
                </button>
            </li>
        </ul>
    </section>
</template>

<script setup>
defineProps({
    calculations: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['delete', 'clear-all']);
</script>

