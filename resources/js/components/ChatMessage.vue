<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  message: {
    type: Object,
    required: true,
    validator: (value) => {
      return (
        typeof value.id === 'string' &&
        (value.sender === 'ai' || value.sender === 'user') &&
        typeof value.text === 'string' &&
        (!value.status || ['sending', 'sent', 'error'].includes(value.status))
      );
    },
  },
});

const emit = defineEmits(['retry']);

// Animation state
const isVisible = ref(false);

// Show the message with a slight delay
onMounted(() => {
  setTimeout(() => {
    isVisible.value = true;
  }, 50);
});

// Use computed properties for classes for better readability
const alignmentClass = computed(() => {
  return props.message.sender === 'ai' ? 'justify-start' : 'justify-end';
});

const isError = computed(() => props.message.status === 'error');

// Minimal bubble styling with error state
const bubbleClass = computed(() => {
  if (isError.value) {
    // Error style: Light red background, darker red text
    return 'bg-red-100 text-red-700';
  }
  return props.message.sender === 'ai'
    ? 'bg-gray-100 text-gray-800' // AI: Light gray bubble
    : 'bg-blue-100 text-blue-900'; // User: Pale blue bubble
});

// Add custom rounded corners based on sender
const roundedCornerClass = computed(() => {
  return props.message.sender === 'ai'
    ? 'rounded-t-2xl rounded-r-2xl rounded-bl-xs' // AI: squared bottom left
    : 'rounded-t-2xl rounded-l-2xl rounded-br-xs'; // User: squared bottom right
});

// Animation classes
const animationClass = computed(() => {
  return isVisible.value 
    ? 'opacity-100 translate-y-0' 
    : 'opacity-0 translate-y-3';
});

const handleRetry = () => {
  emit('retry', props.message.id);
}

</script>

<template>
  <div :class="['flex w-full', alignmentClass]">
    <!-- Using Tailwind for animation -->
    <div 
      :class="[
        'py-2 px-4 text-lg sm:text-xl max-w-xs sm:max-w-md lg:max-w-lg relative group rounded-lg',
        'transition-all duration-300 ease-out transform',
        bubbleClass,
        roundedCornerClass,
        animationClass
      ]"
    >
       <p class="whitespace-pre-wrap">{{ message.text }}</p>

       <!-- Error Indicator & Retry Button -->
       <div v-if="isError" class="mt-1 pt-1 border-t border-red-200">
         <p class="text-xs italic text-red-600 mb-1">Message failed to send</p>
         <button
            @click="handleRetry"
            class="text-xs font-medium text-blue-600 hover:text-blue-800 focus:outline-none"
         >
           Retry
         </button>
       </div>

    </div>
  </div>
</template>

<style scoped>
/* Styles for ChatMessage component */
.whitespace-pre-wrap {
  white-space: pre-wrap; /* Ensures newlines in messages are displayed */
}
</style> 