<script setup>
import { defineProps } from 'vue';

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

// Plan specifies no message bubbles, large text, light colors
// Keep alignment, use larger text, define light colors
const alignmentClass = props.message.sender === 'ai' ? 'justify-start' : 'justify-end';
const textAlignmentClass = props.message.sender === 'ai' ? 'text-left' : 'text-right';
// Use light gray for user, slightly darker gray for AI text for contrast on light bg
const textColor = props.message.sender === 'ai' ? 'text-gray-700' : 'text-gray-600';

</script>

<template>
  <div :class="['flex', alignmentClass]">
    <!-- Increased text size, adjusted padding, added text color -->
    <div :class="['py-2 px-4 text-xl max-w-xl', textAlignmentClass, textColor]">
       {{ message.text }}
    </div>
  </div>
</template>

<style scoped>
/* Styles for ChatMessage component */
</style> 