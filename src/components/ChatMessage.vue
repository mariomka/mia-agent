<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: {
    type: Object,
    required: true,
    validator: (value) => {
      return (
        typeof value.id === 'string' &&
        (value.sender === 'ai' || value.sender === 'user') &&
        typeof value.text === 'string'
      );
    },
  },
});

// Use computed properties for classes for better readability
const alignmentClass = computed(() => {
  return props.message.sender === 'ai' ? 'justify-start' : 'justify-end';
});

// Minimal bubble styling
const bubbleClass = computed(() => {
  return props.message.sender === 'ai'
    ? 'bg-gray-100 text-gray-800' // AI: Light gray bubble, dark text
    : 'bg-blue-100 text-blue-900'; // User: Pale blue bubble, dark blue text
});

</script>

<template>
  <div :class="['flex w-full', alignmentClass]">
    <!-- Add bubble classes: background, rounding, padding -->
    <!-- Max width to prevent bubbles from being full width -->
    <div :class="[
        'py-2 px-4 rounded-lg text-xl max-w-xl lg:max-w-2xl',
        bubbleClass
      ]"
    >
       {{ message.text }}
    </div>
  </div>
</template>

<style scoped>
/* Styles for ChatMessage component */
</style> 