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

const alignmentClass = props.message.sender === 'ai' ? 'justify-start' : 'justify-end';
const bubbleClass = props.message.sender === 'ai'
  ? 'bg-white text-gray-800' // AI style (left)
  : 'bg-blue-500 text-white'; // User style (right)

// Plan specifies no message bubbles, large text, light colors
// Let's adjust - remove bubble, use alignment, larger text
const textAlignmentClass = props.message.sender === 'ai' ? 'text-left' : 'text-right';

</script>

<template>
  <div :class="['flex', alignmentClass]">
    <div :class="['p-3 text-lg max-w-xl', textAlignmentClass]">
       {{ message.text }}
    </div>
    <!-- <div :class="['p-3 rounded-lg max-w-xs lg:max-w-md xl:max-w-lg', bubbleClass]">
      {{ message.text }}
    </div> -->
  </div>
</template>

<style scoped>
/* Styles for ChatMessage component */
</style> 