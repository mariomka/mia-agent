<script setup>
import { onMounted, ref, nextTick, watch } from 'vue';
import { storeToRefs } from 'pinia';
import ChatMessage from '../components/ChatMessage.vue';
import ChatInput from '../components/ChatInput.vue';
import { useChatStore } from '../stores/useChatStore';

const store = useChatStore();
const { messages, isLoading, isInterviewEnded } = storeToRefs(store);

const chatMessagesContainer = ref(null);

// Initialize session on mount
onMounted(() => {
  store.initializeSession();
  // TODO: Maybe add a welcome message here?
  // Example: store.messages.value.push({ id: Date.now().toString(), sender: 'ai', text: 'Welcome!' });
  scrollToBottom();
});

// Placeholder for the actual send message logic
const handleSendMessage = (messageText) => {
  console.log('View received message to send:', messageText);
  // TODO: Call store.sendMessage(messageText) in Step 7

  // Temporary: Add user message directly for UI testing
  store.messages.value.push({
    id: Date.now().toString(), // Temporary ID
    sender: 'user',
    text: messageText,
  });

  // Simulate AI response (replace in Step 7)
  store.isLoading.value = true;
  setTimeout(() => {
    store.messages.value.push({
      id: (Date.now() + 1).toString(), // Temporary ID
      sender: 'ai',
      text: `AI received: "${messageText}". Responding...`,
    });
    store.isLoading.value = false;
  }, 1500);
};

// Function to scroll to the bottom of the chat
const scrollToBottom = async () => {
  await nextTick(); // Wait for the DOM to update
  const container = chatMessagesContainer.value;
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
};

// Watch for new messages and scroll down
watch(messages, () => {
  scrollToBottom();
}, { deep: true });

</script>

<template>
  <div class="flex flex-col h-screen bg-gray-100">
    <!-- Chat messages area -->
    <div ref="chatMessagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
      <ChatMessage
        v-for="message in messages"
        :key="message.id"
        :message="message"
      />
      <!-- Loading indicator -->
      <div v-if="isLoading" class="flex justify-start">
         <div class="p-3 text-lg text-gray-500 italic">
            AI is thinking...
         </div>
      </div>
    </div>

    <!-- Input area -->
    <!-- Conditionally render ChatInput based on interview status -->
    <ChatInput
      v-if="!isInterviewEnded"
      @send-message="handleSendMessage"
      :is-loading="isLoading"
    />
  </div>
</template>

<style scoped>
/* Add any view-specific styles here if needed */
</style> 