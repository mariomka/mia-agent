import { defineStore } from 'pinia';
import { ref } from 'vue';
import { v4 as uuidv4 } from 'uuid';

export const useChatStore = defineStore('chat', () => {
  // State
  const sessionId = ref(null);
  const messages = ref([]); // { id: string, sender: 'ai' | 'user', text: string }
  const isLoading = ref(false);
  const isInterviewEnded = ref(false);
  const finalOutput = ref(null);

  // Actions
  function initializeSession() {
    sessionId.value = uuidv4();
    messages.value = [];
    isLoading.value = false;
    isInterviewEnded.value = false;
    finalOutput.value = null;
    console.log('New session initialized:', sessionId.value);
    // TODO: Optionally add initial welcome message from AI?
  }

  // TODO: Add sendMessage action
  // TODO: Add endInterview action

  // Return state and actions
  return {
    sessionId,
    messages,
    isLoading,
    isInterviewEnded,
    finalOutput,
    initializeSession,
  };
}); 