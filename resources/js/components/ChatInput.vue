<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['sendMessage']);

const messageText = ref('');
const textareaRef = ref(null);
const MAX_CHARS = 200;

const sendMessage = () => {
  const textToSend = messageText.value.trim();
  if (textToSend && !props.isLoading) {
    emit('sendMessage', textToSend);
    messageText.value = ''; // Clear input after sending
    // Reset textarea height after sending
    if (textareaRef.value) textareaRef.value.rows = 1;
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

// Focus the textarea when component is mounted
onMounted(() => {
  if (textareaRef.value) {
    textareaRef.value.focus();
  }
});
</script>

<template>
  <!-- Remove padding, adjust background and border -->
  <div class="bg-white border-t border-gray-100">
    <div class="flex items-center space-x-1 max-w-[800px] mx-auto px-4 py-1 sm:px-6">
      <textarea
        ref="textareaRef"
        v-model="messageText"
        @keydown="handleKeydown"
        @input="handleInput"
        class="flex-1 py-2 px-1 bg-transparent border-none rounded-lg focus:outline-none resize-none transition duration-150 ease-in-out text-base"
        :placeholder="$t('chat.placeholder')"
        rows="1"
        style="min-height: 40px; max-height: 120px;"
        aria-label="Chat message input"
        autofocus
        maxlength="200"
      ></textarea>
      <!-- Icon Button -->
      <button
        @click="sendMessage"
        :disabled="!messageText.trim() || props.isLoading"
        class="p-2 rounded-full text-gray-500 hover:text-blue-600 hover:bg-blue-100 focus:outline-none focus:ring-1 focus:ring-blue-400 focus:ring-offset-1 disabled:text-gray-300 disabled:hover:bg-transparent disabled:cursor-not-allowed transition duration-150 ease-in-out shrink-0"
        :aria-label="$t('chat.sendButton')"
      >
        <!-- SVG Send Icon (Paper Plane) -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
        </svg>
      </button>
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
  background: #f3f4f6; /* Light grey track */
  border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb {
  background-color: #9ca3af; /* Medium grey thumb */
  border-radius: 3px;
  border: 1px solid #f3f4f6; /* Optional: creates padding around thumb */
}
</style> 