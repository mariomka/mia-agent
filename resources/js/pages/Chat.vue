<script setup>
import { onMounted, ref, nextTick, watch } from 'vue';
import { storeToRefs } from 'pinia';
import ChatMessage from '../components/ChatMessage.vue';
import ChatInput from '../components/ChatInput.vue';
import { useChatStore } from '../stores/useChatStore';

const store = useChatStore();
const { messages, isLoading, isInterviewEnded } = storeToRefs(store);
const isInitialized = ref(false);

const chatMessagesContainer = ref(null);

// Initialize session on mount
onMounted(() => {
  store.initializeSession();
  // TODO: Maybe add a welcome message here?
  // Example: store.messages.value.push({ id: Date.now().toString(), sender: 'ai', text: 'Welcome!' });
  scrollToBottom();
});

// Call the store action to send the message
const handleSendMessage = (messageText) => {
  store.sendMessage(messageText);
};

// Call the store action to retry a message
const handleRetryMessage = (messageId) => {
  console.log('View received retry for message ID:', messageId);
  store.retryFailedMessage(messageId);
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
  <div class="flex flex-col h-screen">
    <!-- Chat messages container (full width with scroll) -->
    <div
      ref="chatMessagesContainer"
      class="flex-1 overflow-y-auto w-full"
      aria-live="polite"
      aria-atomic="false">
      <!-- Centered content container -->
      <div class="max-w-[800px] mx-auto p-4 sm:p-6">
        <div class="flex flex-col min-h-full space-y-6">
          <div class="flex-grow"></div>
          <ChatMessage
            v-for="message in messages"
            :key="message.id"
            :message="message"
            @retry="handleRetryMessage"
          />
          <div v-if="isLoading" class="flex justify-start">
             <div class="flex items-center space-x-1 py-2 px-4">
                <span class="dot dot-1"></span>
                <span class="dot dot-2"></span>
                <span class="dot dot-3"></span>
              </div>
          </div>
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
/* Add styles for the waving dots animation */
.dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #a0aec0; /* gray-500 */
  animation: wave 1.3s linear infinite;
}

.dot-1 {
  animation-delay: -0.9s;
}

.dot-2 {
  animation-delay: -0.7s;
}

.dot-3 {
  animation-delay: -0.5s;
}

@keyframes wave {
  0%, 60%, 100% {
    transform: initial;
  }
  30% {
    transform: translateY(-8px); /* Adjust vertical distance */
  }
}
/* Add any view-specific styles here if needed */
</style> 