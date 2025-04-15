import { defineStore } from 'pinia';
import { ref } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import { sendChatMessage } from '../services/apiService'; // Import the API service

export const useChatStore = defineStore('chat', () => {
  // State
  const sessionId = ref(null);
  const messages = ref([]); // { id: string, sender: 'ai' | 'user', text: string }
  const isLoading = ref(false);
  const isInterviewEnded = ref(false);
  const finalOutput = ref(null);
  const error = ref(null); // Add state for potential errors

  // Actions
  function initializeSession() {
    sessionId.value = uuidv4();
    messages.value = [];
    isLoading.value = false;
    isInterviewEnded.value = false;
    finalOutput.value = null;
    error.value = null; // Reset error on new session
    console.log('New session initialized:', sessionId.value);
    // TODO: Send initial message to backend to start the interview?
  }

  // Action to end the interview
  function endInterview(output) {
    isInterviewEnded.value = true;
    finalOutput.value = output;
    isLoading.value = false; // Ensure loading is off
    console.log('Interview ended. Final Output:', output);
    // TODO: Persist final output if needed
  }

  // Action to add a message to the list
  function addMessage(sender, text) {
    messages.value.push({
      id: uuidv4(), // Use UUID for message IDs
      sender,
      text,
    });
  }

  // Action to handle sending a message and receiving response
  async function sendMessage(messageText) {
    if (!sessionId.value || isLoading.value || isInterviewEnded.value) {
      console.warn('sendMessage called inappropriately.', { isLoading: isLoading.value, isInterviewEnded: isInterviewEnded.value });
      return; // Don't send if loading, ended, or no session
    }

    // Add user message immediately
    addMessage('user', messageText);
    isLoading.value = true;
    error.value = null; // Clear previous errors

    try {
      const response = await sendChatMessage(sessionId.value, messageText);

      // Check for AI message
      if (response.output && response.output.message) {
        addMessage('ai', response.output.message);
      }

      // Check for interview end condition
      if (response.output && response.output.final_output !== null && response.output.final_output !== undefined) {
        endInterview(response.output.final_output);
      } else {
        isLoading.value = false; // Turn off loading if interview continues
      }
    } catch (err) {
      console.error('Error during sendMessage:', err);
      error.value = err.message || 'An unknown error occurred.'; // Store error message
      addMessage('ai', `Sorry, I encountered an error: ${error.value}`); // Add error message to chat
      isLoading.value = false; // Ensure loading is turned off on error
      // Optionally end interview on error? Depends on desired behavior.
      // endInterview({ error: error.value });
    }
  }

  // Return state and actions
  return {
    sessionId,
    messages,
    isLoading,
    isInterviewEnded,
    finalOutput,
    error, // Expose error state
    initializeSession,
    sendMessage,
    endInterview, // Expose endInterview if needed externally
  };
}); 