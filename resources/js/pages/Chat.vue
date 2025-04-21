<script setup>
import { onMounted, ref, nextTick, watch, computed } from 'vue';
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

// Function to group messages by sender
const groupedMessages = computed(() => {
  if (!messages.value || messages.value.length === 0) return [];
  
  const result = [];
  let currentGroup = null;
  
  messages.value.forEach((message) => {
    // Start a new group if needed
    if (!currentGroup || currentGroup.sender !== message.sender) {
      if (currentGroup) result.push(currentGroup);
      currentGroup = { sender: message.sender, messages: [message] };
    } else {
      // Add to existing group
      currentGroup.messages.push(message);
    }
  });
  
  // Don't forget to add the last group
  if (currentGroup) result.push(currentGroup);
  
  return result;
});

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
      <!-- Centered content container with flex to push content to bottom -->
      <div class="flex flex-col justify-end min-h-full max-w-[800px] mx-auto p-4 sm:p-6 pb-8">
        <div class="space-y-3">
          <!-- Group messages by sender -->
          <div v-for="(group, groupIndex) in groupedMessages" :key="groupIndex" class="mb-3">
            <!-- Messages in a group have reduced spacing between them -->
            <div v-for="(message, messageIndex) in group.messages" 
                 :key="message.id" 
                 :class="{ 'mt-0.5': messageIndex > 0 }">
              <ChatMessage
                :message="message"
                @retry="handleRetryMessage"
              />
            </div>
          </div>
          
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

    <div v-if="isInterviewEnded" class="p-4">
      <div class="max-w-[800px] mx-auto">
        <div class="flex items-center justify-center">
          <div class="flex justify-center items-center bg-green-50 border border-green-200 rounded-md p-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <h2 class="text-lg font-medium text-gray-900">Interview Complete</h2>
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
</style> 