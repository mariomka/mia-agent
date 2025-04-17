import { defineStore } from 'pinia';
import { ref, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { sendChatMessage } from '../services/apiService.js'; // Import the API service

export const useChatStore = defineStore('chat', () => {
  // Get interview data and sessionId from Inertia props
  const page = usePage();
  const interviewId = ref(page.props.interview?.id);
  const sessionId = ref(page.props.sessionId || null);
  
  // State
  const messages = ref(page.props.messages || []); // Use messages from backend
  const isLoading = ref(false);
  const isInterviewEnded = ref(false);
  const finalOutput = ref(null);
  const error = ref(null); // General store error

  // Actions
  function initializeSession() {
    // Reset transient state
    isLoading.value = false;
    isInterviewEnded.value = false;
    finalOutput.value = null;
    error.value = null;
    
    // Check if the last message needs retrying
    if (messages.value.length > 0) {
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

      // Add AI response message
      if (response.output && response.output.message) {
        const aiMessage = {
          id: `${sessionId.value}_${Date.now() + 1}`,
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

      // Add AI response message if not already present
      if (response.output && response.output.message) {
        // Check if there's already an AI response after this message
        const messagePosition = findMessageIndexById(messageId);
        const nextMessage = messagePosition < messages.value.length - 1 ? 
                           messages.value[messagePosition + 1] : null;
        
        // Only add if there's no next message or the next one isn't from the AI
        if (!nextMessage || nextMessage.sender !== 'ai') {
          const aiMessage = {
            id: `${sessionId.value}_${Date.now()}`,
            sender: 'ai',
            text: response.output.message,
            status: 'sent',
          };
          upsertMessage(aiMessage);
        }
      }

      // Check for interview end condition
      if (response.output && response.output.final_output !== null && response.output.final_output !== undefined) {
        endInterview(response.output.final_output); // This sets isLoading = false
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
  function endInterview(output) {
    isInterviewEnded.value = true;
    finalOutput.value = output;
    isLoading.value = false;
    console.log('Interview ended. Final Output:', output);
  }

  // Return state and actions
  return {
    sessionId,
    interviewId,
    messages,
    isLoading,
    isInterviewEnded,
    finalOutput,
    error, // Expose error state
    initializeSession,
    sendMessage,
    retryFailedMessage, // Expose retry action
    endInterview, // Expose endInterview if needed externally
  };
});
