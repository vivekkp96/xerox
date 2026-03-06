<template>
    <div class="custom-tooltip-wrapper" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
        <slot></slot>
        <transition name="fade">
            <div v-if="show" class="custom-tooltip-popup">
                {{ text }}
                <div class="tooltip-arrow"></div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { CONSTANTS } from '../constants';

defineProps({
    text: {
        type: String,
        required: true
    }
});

const show = ref(false);
let showTimeout = null;
let hideTimeout = null;

const handleMouseEnter = (event) => {
    const wrapper = event.currentTarget;
    if (wrapper.querySelector('input:disabled, textarea:disabled, select:disabled')) {
        return;
    }
    showTimeout = setTimeout(() => {
        show.value = true;
        hideTimeout = setTimeout(() => {
            show.value = false;
        }, CONSTANTS.TOOL_TIP_HIDE_DELAY);
    }, CONSTANTS.TOOL_TIP_SHOW_DELAY);
};

const handleMouseLeave = () => {
    clearTimeout(showTimeout);
    clearTimeout(hideTimeout);
    show.value = false;
};
</script>

<style scoped>
.custom-tooltip-wrapper {
    position: relative;
    display: inline-block;
}

.custom-tooltip-popup {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 8px;
    background-color: #374151;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 50;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    pointer-events: none;
    font-weight: 500;
}

.tooltip-arrow {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 5px;
    border-style: solid;
    border-color: #374151 transparent transparent transparent;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
