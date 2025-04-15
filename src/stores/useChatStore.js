import { defineStore } from 'pinia';
import { ref } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import { sendChatMessage } from '../services/apiService'; // Import the API service

export const useChatStore = defineStore('chat', () => {
  // State
  const sessionId = ref(null);
  const messages = ref([]); // { id, sender, text, status: 'sending'|'sent'|'error', error?: string }
  const isLoading = ref(false);
  const isInterviewEnded = ref(false);
  const finalOutput = ref(null);
  const error = ref(null); // General store error (maybe remove if errors are per-message?)
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

             // Check if the last message needs retrying
             if (messages.value.length > 0) {
                const lastMessage = messages.value[messages.value.length - 1];
                if (lastMessage.sender === 'user') {
                    console.log('Last message was from user, attempting retry to ensure AI response:', lastMessage.id);
                    // Pass force: true for automatic retry on load
                    retryFailedMessage(lastMessage.id, { force: true });
                }
             }
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

  // Helper to find message index by ID
  function findMessageIndexById(id) {
    return messages.value.findIndex(m => m.id === id);
  }

  // Helper to update sessionStorage for messages
  function updateStoredMessages() {
    try {
        sessionStorage.setItem(MESSAGES_STORAGE_KEY, JSON.stringify(messages.value));
    } catch (e) {
        console.error('Failed to save messages to sessionStorage:', e);
    }
  }

  // Action to add/update a message and save
  function upsertMessage(messageData) {
      const index = findMessageIndexById(messageData.id);
      if (index > -1) {
          // Update existing message
          messages.value[index] = { ...messages.value[index], ...messageData };
      } else {
          // Add new message
          messages.value.push(messageData);
      }
  }

  // Action to handle sending a message
  async function sendMessage(messageText) {
    if (!sessionId.value || isInterviewEnded.value || isLoading.value) return;

    const userMessageId = uuidv4(); // Generate ID upfront
    const userMessage = {
        id: userMessageId,
        sender: 'user',
        text: messageText,
        status: 'sending',
        error: undefined, // Clear any previous error for this potential retry
    };
    upsertMessage(userMessage);
    isLoading.value = true;
    error.value = null; // Clear general store error

    try {
      const response = await sendChatMessage(sessionId.value, messageText);

      // Update user message status to 'sent'
      upsertMessage({ id: userMessageId, status: 'sent' });

      // Add AI response message
      if (response.output && response.output.message) {
        const aiMessage = {
            id: uuidv4(),
            sender: 'ai',
            text: response.output.message,
            status: 'sent', // AI messages are considered 'sent'
        };
        upsertMessage(aiMessage);
      }

      // Check for interview end condition
      if (response.output && response.output.final_output !== null && response.output.final_output !== undefined) {
        endInterview(response.output.final_output);
      } else {
        isLoading.value = false;
      }
    } catch (err) {
      console.error('Error during sendMessage:', err);
      const errorMessage = err.message || 'An unknown error occurred.';
      // Update user message status to 'error'
      upsertMessage({ id: userMessageId, status: 'error', error: errorMessage });
      isLoading.value = false;
      // Maybe set general error too?
      // error.value = errorMessage;
    }
    updateStoredMessages();
  }

  // Action to retry sending a failed message
  // Added options param { force?: boolean }
  async function retryFailedMessage(messageId, options = {}) {
      const { force = false } = options;
      const messageIndex = findMessageIndexById(messageId);
      if (messageIndex === -1) {
        console.error('Message not found for retry:', messageId);
        return;
      }

      const messageToRetry = messages.value[messageIndex];
      // Updated guard: Check force flag
      const isRetryableStatus = messageToRetry.status === 'error' || messageToRetry.status === 'sending';
      if (messageToRetry.sender !== 'user' || (!force && !isRetryableStatus)) {
          console.warn(`Cannot retry message (sender: ${messageToRetry.sender}, status: ${messageToRetry.status}, force: ${force}):`, messageToRetry);
          return;
      }

      console.log('Retrying message:', messageToRetry.text);

      // Update status to 'sending' and clear error immediately
      upsertMessage({ id: messageId, status: 'sending', error: undefined });
      isLoading.value = true;
      error.value = null; // Clear any general store error

      try {
        const response = await sendChatMessage(sessionId.value, messageToRetry.text);

        // Update original user message status to 'sent'
        upsertMessage({ id: messageId, status: 'sent' });

        // Check for interview end condition
        if (response.output && response.output.final_output !== null && response.output.final_output !== undefined) {
          endInterview(response.output.final_output); // This sets isLoading = false
        } else {
          isLoading.value = false; // Set loading false if interview didn't end
        }
      } catch (err) {
        console.error('Error during retryFailedMessage:', err);
        const errorMessage = err.message || 'An unknown error occurred during retry.';
        // Update original user message status back to 'error' with new error message
        upsertMessage({ id: messageId, status: 'error', error: errorMessage });
        isLoading.value = false;
        // Optionally set general error
        // error.value = errorMessage;
      }
      updateStoredMessages();
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

  // Action to clear session storage
  function clearSession() {
     sessionStorage.removeItem(SESSION_ID_STORAGE_KEY);
     sessionStorage.removeItem(MESSAGES_STORAGE_KEY);
     console.log('Session ID and messages cleared from storage.');
     // initializeSession(); // Optionally start a new session
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
    retryFailedMessage, // Expose retry action
    endInterview, // Expose endInterview if needed externally
    clearSession, // Expose clearSession
  };
}); 