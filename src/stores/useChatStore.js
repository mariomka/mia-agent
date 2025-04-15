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
    const existingSessionId = sessionStorage.getItem('miaSessionId');

    // Always reset state on initialization, regardless of new/existing session
    messages.value = [];
    isLoading.value = false;
    isInterviewEnded.value = false;
    finalOutput.value = null;
    error.value = null;

    if (existingSessionId) {
      sessionId.value = existingSessionId;
      console.log('Existing session found and reused:', sessionId.value);
      // TODO: If we wanted to restore messages, we would load them from sessionStorage here.
      // addMessage('ai', 'Welcome back!'); // Example welcome back message
    } else {
      sessionId.value = uuidv4();
      sessionStorage.setItem('miaSessionId', sessionId.value);
      console.log('New session initialized and stored:', sessionId.value);
      // TODO: Add initial welcome message or fetch initial state from backend?
      // addMessage('ai', 'Welcome! Please tell me about...'); // Example new session message
    }
  }

  // Action to clear session storage
  function clearSession() {
     sessionStorage.removeItem('miaSessionId');
     console.log('Session cleared from storage.');
     // Optionally, immediately start a new session:
     // initializeSession();
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
        // Clear session storage when interview naturally ends?
        // clearSession(); // Uncomment if desired
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

  // Action to end the interview
  function endInterview(output) {
    isInterviewEnded.value = true;
    finalOutput.value = output;
    isLoading.value = false; // Ensure loading is off
    console.log('Interview ended. Final Output:', output);
    // TODO: Persist final output if needed
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
    clearSession, // Expose clearSession
  };
}); 