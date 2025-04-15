<script setup>
import { ref } from 'vue';

const messageText = ref('');

// TODO: Define emits for sending message
// const emit = defineEmits(['sendMessage']);

const sendMessage = () => {
  if (messageText.value.trim()) {
    console.log('Sending:', messageText.value); // Placeholder
    // emit('sendMessage', messageText.value.trim());
    messageText.value = ''; // Clear input after sending
  }
};

// Handle Enter key press (Shift+Enter for new line)
const handleKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault(); // Prevent default newline behavior
    sendMessage();
  }
};

</script>

<template>
  <div class="p-4 bg-white border-t border-gray-200">
    <div class="flex items-end space-x-2">
      <textarea
        v-model="messageText"
        @keydown="handleKeydown"
        class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
        placeholder="Type your message... (Shift+Enter for new line)"
        rows="1"
        style="min-height: 44px; max-height: 150px;"
        @input="$event.target.rows = messageText.split('\n').length > 1 ? Math.min(messageText.split('\n').length, 5) : 1"
      ></textarea>
      <button
        @click="sendMessage"
        :disabled="!messageText.trim()"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out"
      >
        Send
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