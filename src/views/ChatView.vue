<script setup>
// Import components and store later
// import ChatMessage from '../components/ChatMessage.vue';
// import ChatInput from '../components/ChatInput.vue';
// import { useChatStore } from '../stores/useChatStore';
// import { onMounted } from 'vue';

// const store = useChatStore();

// onMounted(() => {
//   store.initializeSession();
// });

// Dummy data for now
const messages = [
  { id: '1', sender: 'ai', text: 'Hello! How can I help you today?' },
  { id: '2', sender: 'user', text: 'I need help with my account.' },
  { id: '3', sender: 'ai', text: 'Okay, what seems to be the problem?' },
];
const isLoading = false;
const isInterviewEnded = false;

</script>

<template>
  <div class="flex flex-col h-screen bg-gray-100">
    <!-- Chat messages area -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4">
      <div v-for="message in messages" :key="message.id">
        <!-- ChatMessage component will go here -->
        <div
          :class="[
            'p-3 rounded-lg max-w-xs lg:max-w-md xl:max-w-lg',
            message.sender === 'ai' ? 'bg-white text-gray-800 mr-auto' : 'bg-blue-500 text-white ml-auto'
          ]"
        >
          {{ message.text }}
        </div>
      </div>
      <!-- Loading indicator -->
      <div v-if="isLoading" class="flex justify-center">
        <p class="text-gray-500 italic">AI is thinking...</p>
      </div>
    </div>

    <!-- Input area -->
    <div v-if="!isInterviewEnded" class="p-4 bg-white border-t border-gray-200">
      <!-- ChatInput component will go here -->
      <div class="flex space-x-2">
        <textarea class="flex-1 p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..."></textarea>
        <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Send</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add any view-specific styles here if needed */
</style> 