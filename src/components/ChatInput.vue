<script setup>
import { ref, defineEmits, defineProps } from 'vue';

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['sendMessage']);

const messageText = ref('');

const sendMessage = () => {
  const textToSend = messageText.value.trim();
  if (textToSend && !props.isLoading) {
    emit('sendMessage', textToSend);
    messageText.value = ''; // Clear input after sending
    // Reset textarea height after sending
    const textarea = document.querySelector('textarea'); // Find the textarea element
    if (textarea) textarea.rows = 1;
  }
};

// Handle Enter key press (Shift+Enter for new line)
const handleKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault(); // Prevent default newline behavior
    sendMessage();
  }
};

// Basic auto-resize logic for textarea
const handleInput = (event) => {
  const textarea = event.target;
  textarea.rows = 1; // Reset rows to recalculate height
  const lines = textarea.value.split('\n').length;
  const maxRows = 5; // Maximum number of rows before scrolling
  textarea.rows = Math.min(lines, maxRows);
};

</script>

<template>
  <div class="p-4 bg-white border-t border-gray-200">
    <div class="flex items-end space-x-2">
      <textarea
        ref="textareaRef" // Add ref for potential future use
        v-model="messageText"
        @keydown="handleKeydown"
        @input="handleInput"
        :disabled="props.isLoading"
        class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none disabled:bg-gray-100 disabled:cursor-not-allowed transition duration-150 ease-in-out"
        placeholder="Type your message... (Shift+Enter for new line)"
        rows="1"
        style="min-height: 44px; max-height: 150px;"
      ></textarea>
      <button
        @click="sendMessage"
        :disabled="!messageText.trim() || props.isLoading"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out shrink-0"
      >
        <!-- Optional: Show loading state on button -->
        <span v-if="!props.isLoading">Send</span>
        <span v-else>...</span>
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