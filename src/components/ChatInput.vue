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
        ref="textareaRef"
        v-model="messageText"
        @keydown="handleKeydown"
        @input="handleInput"
        :disabled="props.isLoading"
        class="flex-1 p-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 focus:border-blue-400 resize-none disabled:bg-gray-100 disabled:cursor-not-allowed transition duration-150 ease-in-out text-base"
        placeholder="Type your message..."
        rows="1"
        style="min-height: 44px; max-height: 150px;"
      ></textarea>
      <button
        @click="sendMessage"
        :disabled="!messageText.trim() || props.isLoading"
        class="px-5 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed transition duration-150 ease-in-out shrink-0 text-base font-medium"
      >
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