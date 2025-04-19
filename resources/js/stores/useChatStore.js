import { defineStore } from 'pinia';
import { ref, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { sendChatMessage, initializeChat } from '../services/apiService.js'; // Import the new API function

export const useChatStore = defineStore('chat', () => {
  // Get interview data and sessionId from Inertia props
  const page = usePage();
  const interviewId = ref(page.props.interview?.id);
  const sessionId = ref(page.props.sessionId || null);
  
  // State
  const messages = ref(page.props.messages || []); // Use messages from backend
  const isLoading = ref(false);
  const isInterviewEnded = ref(false);
  const interviewResult = ref(null);
  const error = ref(null); // General store error

  // Actions
  function initializeSession() {
    // Reset transient state
    isLoading.value = false;
    isInterviewEnded.value = false;
    interviewResult.value = null;
    error.value = null;
    
    // Check if messages are empty or if the last message needs retrying
    if (messages.value.length === 0) {
      // If no messages, initialize the chat with the first AI message
      fetchInitialAIMessage();
    } else if (messages.value.length > 0) {
      const lastMessage = messages.value[messages.value.length - 1];
      if (lastMessage.sender === 'user') {
        // Pass force: true for automatic retry on load
        retryFailedMessage(lastMessage.id, { force: true });
      }
    }
  }

  // Helper to find message index by ID
  function findMessageIndexById(id) {
    return messages.value.findIndex(m => m.id === id);
  }

  // Action to add/update a message
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

  // Action to fetch the initial AI message
  async function fetchInitialAIMessage() {
    if (!sessionId.value || !interviewId.value || isInterviewEnded.value) return;

    isLoading.value = true;
    error.value = null; // Clear general store error

    try {
      const response = await initializeChat(sessionId.value, interviewId.value);

      // Add AI welcome messages
      if (response.output && response.output.messages && Array.isArray(response.output.messages)) {
        // Process each message in the array (limited to max 3 messages by the agent)
        response.output.messages.forEach((messageText, index) => {
          const aiMessage = {
            id: `${sessionId.value}_${Date.now()}_${index}`,
            sender: 'ai',
            text: messageText,
            status: 'sent', // AI messages are considered 'sent'
          };
          upsertMessage(aiMessage);
        });
      }

      // Check for interview end condition using the 'finished' flag
      if (response.output && response.output.finished) {
        endInterview(response.output.result);
      } else {
        isLoading.value = false;
      }
    } catch (err) {
      const errorMessage = err.message || 'An unknown error occurred during initialization.';
      error.value = errorMessage;
      isLoading.value = false;
    }
  }

  // Action to handle sending a message
  async function sendMessage(messageText) {
    if (!sessionId.value || !interviewId.value || isInterviewEnded.value || isLoading.value) return;

    const userMessageId = `${sessionId.value}_${Date.now()}`;
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
      const response = await sendChatMessage(sessionId.value, messageText, interviewId.value);

      // Update user message status to 'sent'
      upsertMessage({ id: userMessageId, status: 'sent' });

      // Add AI response messages
      if (response.output && response.output.messages && Array.isArray(response.output.messages)) {
        // Process each message in the array (limited to max 3 messages by the agent)
        response.output.messages.forEach((messageText, index) => {
          const aiMessage = {
            id: `${sessionId.value}_${Date.now()}_${index}`,
            sender: 'ai',
            text: messageText,
            status: 'sent', // AI messages are considered 'sent'
          };
          upsertMessage(aiMessage);
        });
      }

      // Check for interview end condition using the 'finished' flag
      if (response.output && response.output.finished) {
        endInterview(response.output.result);
      } else {
        isLoading.value = false;
      }
    } catch (err) {
      const errorMessage = err.message || 'An unknown error occurred.';
      // Update user message status to 'error'
      upsertMessage({ id: userMessageId, status: 'error', error: errorMessage });
      isLoading.value = false;
    }
  }

  // Action to retry sending a failed message
  async function retryFailedMessage(messageId, options = {}) {
    const { force = false } = options;
    const messageIndex = findMessageIndexById(messageId);
    if (messageIndex === -1) {
      return;
    }

    const messageToRetry = messages.value[messageIndex];
    // Updated guard: Check force flag
    const isRetryableStatus = messageToRetry.status === 'error' || messageToRetry.status === 'sending';
    if (messageToRetry.sender !== 'user' || (!force && !isRetryableStatus)) {
      return;
    }

    // Update status to 'sending' and clear error immediately
    upsertMessage({ id: messageId, status: 'sending', error: undefined });
    isLoading.value = true;
    error.value = null; // Clear any general store error

    try {
      const response = await sendChatMessage(sessionId.value, messageToRetry.text, interviewId.value);

      // Update original user message status to 'sent'
      upsertMessage({ id: messageId, status: 'sent' });

      // Add AI response messages
      if (response.output && response.output.messages && Array.isArray(response.output.messages)) {
        // Find current message position
        const messagePosition = findMessageIndexById(messageId);
        
        // Remove any AI messages that might be after this user message
        let nextIndex = messagePosition + 1;
        while (nextIndex < messages.value.length && messages.value[nextIndex].sender === 'ai') {
          messages.value.splice(nextIndex, 1);
        }
        
        // Add the new AI messages
        response.output.messages.forEach((messageText, index) => {
          const aiMessage = {
            id: `${sessionId.value}_${Date.now()}_${index}`,
            sender: 'ai',
            text: messageText,
            status: 'sent',
          };
          upsertMessage(aiMessage);
        });
      }

      // Check for interview end condition using the 'finished' flag
      if (response.output && response.output.finished) {
        endInterview(response.output.result); // This sets isLoading = false
      } else {
        isLoading.value = false; // Set loading false if interview didn't end
      }
    } catch (err) {
      const errorMessage = err.message || 'An unknown error occurred during retry.';
      // Update original user message status back to 'error' with new error message
      upsertMessage({ id: messageId, status: 'error', error: errorMessage });
      isLoading.value = false;
    }
  }

  // Action to end the interview
  function endInterview(result) {
    isInterviewEnded.value = true;
    interviewResult.value = result;
    isLoading.value = false;
    console.log('Interview ended. Result:', result);
  }

  // Return state and actions
  return {
    sessionId,
    interviewId,
    messages,
    isLoading,
    isInterviewEnded,
    interviewResult,
    error, // Expose error state
    initializeSession,
    sendMessage,
    retryFailedMessage, // Expose retry action
    endInterview, // Expose endInterview if needed externally
    fetchInitialAIMessage, // Expose initialization function
  };
});
