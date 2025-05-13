<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['sendMessage']);

const messageText = ref('');
const textareaRef = ref(null);
const isFocused = ref(false);
const MAX_CHARS = 200;

const sendMessage = () => {
  const textToSend = messageText.value.trim();
  if (textToSend && !props.isLoading) {
    emit('sendMessage', textToSend);
    messageText.value = ''; // Clear input after sending
    // Reset textarea height after sending
    if (textareaRef.value) {
      textareaRef.value.rows = 1;
      // Refocus the textarea after sending
      textareaRef.value.focus();
    }
  }
};

// Handle Enter key press (Shift+Enter for new line)
const handleKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault(); // Prevent default newline behavior
    if (!props.isLoading) {
      sendMessage();
    }
  }
};

// Basic auto-resize logic for textarea
const handleInput = (event) => {
  const textarea = event.target;

  // Limit text to MAX_CHARS characters
  if (textarea.value.length > MAX_CHARS) {
    textarea.value = textarea.value.substring(0, MAX_CHARS);
    messageText.value = textarea.value;
  }

  textarea.rows = 1; // Reset rows to recalculate height
  const lines = textarea.value.split('\n').length;
  const maxRows = 5; // Maximum number of rows before scrolling
  textarea.rows = Math.min(lines, maxRows);
};

// Character counter logic
const remainingChars = computed(() => MAX_CHARS - messageText.value.length);
const charCounterClass = computed(() => {
  if (remainingChars.value <= 20) return 'text-red-500';
  if (remainingChars.value <= 50) return 'text-amber-500';
  return 'text-gray-400';
});

// Focus the textarea when component is mounted
onMounted(() => {
  if (textareaRef.value) {
    textareaRef.value.focus();
  }
});

const handleFocus = () => {
  isFocused.value = true;
};

const handleBlur = () => {
  isFocused.value = false;
};

// Compute send button classes
const sendButtonClass = computed(() => {
  const baseClasses = 'p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200 ease-in-out shrink-0';

  if (!messageText.value.trim() || props.isLoading) {
    return `${baseClasses} text-gray-400 bg-gray-100`;
  }

  return `${baseClasses} text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg`;
});
</script>

<template>
  <!-- Modern glass morphism input container -->
  <div class="bg-gradient-to-r from-indigo-50/80 to-purple-50/80 backdrop-blur-md border-t border-indigo-100/50 shadow-xs">
    <div class="flex flex-col max-w-[800px] mx-auto px-4 py-3 sm:px-6">
      <!-- Input area with glass effect -->
      <div
        class="flex items-center space-x-2 bg-white/70 backdrop-blur-sm rounded-2xl px-4 py-2 border border-indigo-100/50 shadow-xs transition-all duration-300 ease-in-out"
        :class="{ 'border-indigo-300/70 shadow-md ring-1 ring-indigo-300/30': isFocused }"
      >
        <textarea
          ref="textareaRef"
          v-model="messageText"
          @keydown="handleKeydown"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          class="flex-1 py-2 px-1 bg-transparent border-none rounded-lg focus:outline-none resize-none transition duration-150 ease-in-out text-base"
          :placeholder="$t('chat.placeholder')"
          rows="1"
          style="min-height: 40px; max-height: 120px;"
          aria-label="Chat message input"
          autofocus
          maxlength="200"
        ></textarea>

        <button
          @click="sendMessage"
          :disabled="!messageText.trim() || props.isLoading"
          :class="sendButtonClass"
          :aria-label="$t('chat.sendButton')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
          </svg>
        </button>
      </div>

      <!-- Character counter and loading state -->
      <div class="flex justify-between items-center mt-1 px-2">
        <div :class="['text-xs transition-all', charCounterClass]">
          {{ remainingChars }} {{ $t('chat.charactersRemaining') }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
textarea {
  scrollbar-width: thin; /* For Firefox */
  scrollbar-color: #9ca3af #f3f4f6; /* For Firefox - thumb and track */
}

/* For Chrome, Edge, and Safari */
textarea::-webkit-scrollbar {
  width: 6px;
}

textarea::-webkit-scrollbar-track {
  background: transparent; /* Transparent track */
  border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb {
  background-color: #9ca3af; /* Medium grey thumb */
  border-radius: 3px;
  border: 1px solid #f3f4f6; /* Optional: creates padding around thumb */
}

/* Improved focus state */
textarea:focus {
  outline: none;
  box-shadow: none;
}

/* Animation for send button */
button {
  transform: scale(1);
  transition: transform 0.2s ease;
}

button:not(:disabled):hover {
  transform: scale(1.05);
}

button:not(:disabled):active {
  transform: scale(0.95);
}
</style>
