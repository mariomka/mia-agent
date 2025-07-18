<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
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
  <div class="flex flex-col h-full overflow-hidden">
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

          <div v-if="isInterviewEnded" class="pt-4">
            <div class="flex items-center justify-center">
              <div class="flex justify-center items-center bg-green-50 border border-green-200 rounded-md p-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-green-500 mr-3" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h2 class="text-lg font-medium text-gray-900">{{ $t('chat.interviewComplete') }}</h2>
              </div>
            </div>
          </div>

          <div v-if="isLoading" class="flex justify-start">
            <div class="bg-white/80 backdrop-blur-sm border border-indigo-100/30 shadow-xs rounded-2xl py-3 px-4">
              <div class="flex items-center space-x-2">
                <div class="typing-indicator">
                  <span class="dot dot-1"></span>
                  <span class="dot dot-2"></span>
                  <span class="dot dot-3"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ChatInput
      v-if="!isInterviewEnded"
      @send-message="handleSendMessage"
      :is-loading="isLoading"
    />
  </div>
</template>

<style scoped>
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #818cf8; /* indigo-400 */
  animation: wave 1.3s linear infinite, colorChange 3s infinite alternate;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dot-1 {
  animation-delay: -0.9s, -0.3s;
}

.dot-2 {
  animation-delay: -0.7s, -0.6s;
}

.dot-3 {
  animation-delay: -0.5s, -0.9s;
}

@keyframes wave {
  0%, 60%, 100% {
    transform: initial;
  }
  30% {
    transform: translateY(-8px); /* Adjust vertical distance */
  }
}

@keyframes colorChange {
  0% {
    background-color: #818cf8; /* indigo-400 */
  }
  50% {
    background-color: #a78bfa; /* purple-400 */
  }
  100% {
    background-color: #818cf8; /* back to indigo-400 */
  }
}
</style>
