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
  const MESSAGES_STORAGE_KEY = 'miaMessages';
  const SESSION_ID_STORAGE_KEY = 'miaSessionId';

  // Actions
  function initializeSession() {
    const existingSessionId = sessionStorage.getItem(SESSION_ID_STORAGE_KEY);

    // Always reset transient state
    isLoading.value = false;
    isInterviewEnded.value = false;
    finalOutput.value = null;
    error.value = null;

    if (existingSessionId) {
      sessionId.value = existingSessionId;
      console.log('Existing session found and reused:', sessionId.value);

      // Try to load messages from storage
      try {
        const storedMessages = sessionStorage.getItem(MESSAGES_STORAGE_KEY);
        if (storedMessages) {
          const parsedMessages = JSON.parse(storedMessages);
          // Basic validation - check if it's an array
          if (Array.isArray(parsedMessages)) {
             messages.value = parsedMessages;
             console.log('Loaded messages from sessionStorage.');
          } else {
             console.warn('Invalid messages format found in sessionStorage. Starting fresh.');
             messages.value = [];
          }
        } else {
          messages.value = []; // No messages stored
        }
      } catch (e) {
        console.error('Failed to parse messages from sessionStorage:', e);
        messages.value = []; // Start fresh on error
        sessionStorage.removeItem(MESSAGES_STORAGE_KEY); // Clear invalid data
      }

    } else {
      // New session
      sessionId.value = uuidv4();
      messages.value = [];
      sessionStorage.setItem(SESSION_ID_STORAGE_KEY, sessionId.value);
      sessionStorage.removeItem(MESSAGES_STORAGE_KEY); // Clear any old messages
      console.log('New session initialized and stored:', sessionId.value);
      // TODO: Add initial welcome message?
      // addMessage('ai', 'Welcome! Please tell me about...');
    }
    // Persist initial state (empty messages if new session)
    updateStoredMessages();
  }

  // Helper to update sessionStorage for messages
  function updateStoredMessages() {
    try {
        sessionStorage.setItem(MESSAGES_STORAGE_KEY, JSON.stringify(messages.value));
    } catch (e) {
        console.error('Failed to save messages to sessionStorage:', e);
    }
  }

  // Action to clear session storage
  function clearSession() {
     sessionStorage.removeItem(SESSION_ID_STORAGE_KEY);
     sessionStorage.removeItem(MESSAGES_STORAGE_KEY);
     console.log('Session ID and messages cleared from storage.');
     // initializeSession(); // Optionally start a new session
  }

  // Action to add a message to the list and update storage
  function addMessage(sender, text) {
    messages.value.push({
      id: uuidv4(),
      sender,
      text,
    });
    updateStoredMessages(); // Update storage whenever a message is added
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
    isLoading.value = false;
    console.log('Interview ended. Final Output:', output);
    // Clear storage when interview ends?
    // clearSession();
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