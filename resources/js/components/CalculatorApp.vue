<template>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md rounded-xl border border-border bg-surface p-5">
            <header class="mb-4">
                <h1 class="text-lg font-semibold text-text">
                    CalcTek Calculator
                </h1>
            </header>

            <form
                class="space-y-3"
                @submit.prevent="submit"
            >
                <label class="block text-[11px] font-medium text-text-muted">
                    Expression
                    <input
                        ref="inputRef"
                        v-model="expression"
                        type="text"
                        name="expression"
                        autocomplete="off"
                        class="mt-1 block w-full rounded-md border border-border bg-surface px-3 py-2 text-sm font-mono text-text placeholder:text-text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/70"
                        placeholder="e.g. sqrt((((9*9)/12)+(13-4))*2)^2"
                    />
                </label>

                <!-- Calculator keypad -->
                <div class="grid grid-cols-4 gap-2 text-sm">
                    <template
                        v-for="btn in buttons"
                        :key="btn.label + btn.value"
                    >
                        <button
                            v-if="btn.variant !== 'placeholder'"
                            type="button"
                            :class="keyClasses(btn)"
                            @click="handleButton(btn)"
                        >
                            {{ btn.label }}
                        </button>
                        <div
                            v-else
                        ></div>
                    </template>
                </div>

                <p
                    v-if="lastResultMessage"
                    :class="[
                        'text-[11px]',
                        lastHadError ? 'text-danger' : 'text-success',
                    ]"
                >
                    {{ lastResultMessage }}
                </p>
            </form>

            <TickerTape
                class="mt-5"
                :calculations="calculations"
                @delete="handleDelete"
                @clear-all="handleClearAll"
            />
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import {
    clearCalculations,
    createCalculation,
    deleteCalculation,
    fetchCalculations,
} from '../api/calculations';
import TickerTape from './TickerTape.vue';

const expression = ref('');
const submitting = ref(false);
const calculations = ref([]);
const lastResultMessage = ref('');
const lastHadError = ref(false);
const inputRef = ref(null);

const buttons = [
    // Top row: specials + division on the right
    { label: 'C', value: 'C', variant: 'action' },
    { label: 'sqrt', value: 'sqrt(', variant: 'operator' },
    { label: 'EXP', value: '^', variant: 'operator' },
    { label: '÷', value: '/', variant: 'operator' },

    // Number rows with operators aligned on the right column
    { label: '7', value: '7' },
    { label: '8', value: '8' },
    { label: '9', value: '9' },
    { label: '×', value: '*', variant: 'operator' },

    { label: '4', value: '4' },
    { label: '5', value: '5' },
    { label: '6', value: '6' },
    { label: '−', value: '-', variant: 'operator' },

    { label: '1', value: '1' },
    { label: '2', value: '2' },
    { label: '3', value: '3' },
    { label: '+', value: '+', variant: 'operator' },

    // Bottom numeric row
    { label: '0', value: '0' },
    { label: '.', value: '.' },
    { label: '(', value: '(' },
    { label: ')', value: ')' },

    // Last row: backspace + big equals on the right
    { label: '⌫', value: 'BACK', variant: 'action' },
    { label: '', value: 'FILL', variant: 'placeholder' },
    { label: '=', value: 'EQUAL', variant: 'equal' },
    { label: '', value: 'FILL', variant: 'placeholder' },
];

function keyClasses(btn) {
    if (btn.variant === 'equal') {
        return [
            'flex items-center justify-center rounded-md px-0 py-3 text-base font-medium col-span-2',
            'bg-primary border border-primary text-slate-950',
            'transition-colors duration-150 hover:bg-primary-hover',
        ];
    }

    const classes = [
        'flex items-center justify-center rounded-md px-0 py-2 font-medium',
        'border border-border bg-surface text-text',
        'transition-colors duration-150 hover:bg-surface hover:border-primary hover:text-text',
    ];

    if (btn.variant === 'operator') {
        classes.push('text-operator');
    }

    if (btn.variant === 'action') {
        classes.push('text-danger');
    }

    return classes;
}

async function loadCalculations() {
    try {
        calculations.value = await fetchCalculations();
    } catch (error) {
        console.error(error);
    }
}

async function submit() {
    if (!expression.value.trim()) {
        return;
    }

    submitting.value = true;
    lastResultMessage.value = '';

    try {
        const created = await createCalculation(expression.value);
        calculations.value = [created, ...calculations.value];

        lastHadError.value = created.had_error;
        lastResultMessage.value = created.had_error
            ? created.error_message || 'The expression could not be evaluated.'
            : `Result: ${created.result}`;

        expression.value = '';
        focusInput();
    } catch (error) {
        console.error(error);
        lastHadError.value = true;
        lastResultMessage.value = 'Request failed. Please try again.';
    } finally {
        submitting.value = false;
    }
}

async function handleDelete(id) {
    try {
        await deleteCalculation(id);
        calculations.value = calculations.value.filter((c) => c.id !== id);
    } catch (error) {
        console.error(error);
    }
}

async function handleClearAll() {
    try {
        await clearCalculations();
        calculations.value = [];
    } catch (error) {
        console.error(error);
    }
}

function handleButton(btn) {
    if (btn.value === 'C') {
        expression.value = '';
        lastResultMessage.value = '';
        lastHadError.value = false;
        focusInput();
        return;
    }

    if (btn.value === 'BACK') {
        expression.value = expression.value.slice(0, -1);
        focusInput();
        return;
    }

    if (btn.value === 'EQUAL') {
        submit();
        return;
    }

    expression.value += btn.value;
    focusInput();
}

function focusInput() {
    if (inputRef.value) {
        inputRef.value.focus();
    }
}

onMounted(() => {
    focusInput();
    loadCalculations();
});
</script>

