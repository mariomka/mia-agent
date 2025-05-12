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

// Show the message with a slight delay and staggered animation
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
const isSending = computed(() => props.message.status === 'sending');

const bubbleClass = computed(() => {
  if (isError.value) {
    return 'bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-700 shadow-xs';
  }
  return props.message.sender === 'ai'
    ? 'bg-purple-50/90 backdrop-blur-sm border border-purple-100 text-gray-800 shadow-xs' // AI: Subtle purple solid color
    : 'bg-indigo-50/90 backdrop-blur-sm border border-indigo-100 text-indigo-900 shadow-xs'; // User: Indigo tinted glass
});

const roundedCornerClass = computed(() => {
  return props.message.sender === 'ai'
    ? 'rounded-t-2xl rounded-br-2xl rounded-bl-sm' // AI: rounded except top-left
    : 'rounded-t-2xl rounded-br-sm rounded-bl-2xl'; // User: rounded except top-right and bottom-right
});

const animationClass = computed(() => {
  const baseAnimation = isVisible.value
    ? 'opacity-100 translate-y-0'
    : 'opacity-0 translate-y-3';

  const slideDirection = props.message.sender === 'ai'
    ? 'animate-slide-in-from-left'
    : 'animate-slide-in-from-right';

  return `${baseAnimation} ${isVisible.value ? slideDirection : ''}`;
});

const handleRetry = () => {
  emit('retry', props.message.id);
}

</script>

<template>
  <div :class="['flex w-full mb-3', alignmentClass]">
    <!-- Using Tailwind for animation -->
    <div
      :class="[
        'py-3 px-4 text-base sm:text-lg max-w-xs sm:max-w-md lg:max-w-lg relative group',
        'transition-all duration-300 ease-out transform',
        bubbleClass,
        roundedCornerClass,
        animationClass
      ]"
    >
      <p class="whitespace-pre-wrap font-normal leading-relaxed">{{ message.text }}</p>

      <!-- Error Indicator & Retry Button -->
      <div v-if="isError" class="mt-2 pt-1 border-t border-red-200">
        <p class="text-xs italic text-red-600 mb-1">{{ $t('chat.messageFailedToSend') }}</p>
        <button
          @click="handleRetry"
          class="text-xs font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-300 rounded-full py-0.5 transition-all duration-200"
        >
          {{ $t('chat.retry') }}
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

@keyframes slide-in-from-left {
  0% {
    transform: translateX(-10px);
    opacity: 0;
  }
  100% {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slide-in-from-right {
  0% {
    transform: translateX(10px);
    opacity: 0;
  }
  100% {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in-from-left {
  animation: slide-in-from-left 0.3s ease-out;
}

.animate-slide-in-from-right {
  animation: slide-in-from-right 0.3s ease-out;
}
</style>
