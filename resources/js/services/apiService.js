import axios from 'axios';

const N8N_WEBHOOK_URL = 'http://localhost:5678/webhook/d73aaa78-0487-4818-9364-fdf93f37a45d/chat';

/**
 * Sends a message to the n8n chat webhook.
 * @param {string} sessionId - The unique session identifier.
 * @param {string} chatInput - The user's message text.
 * @returns {Promise<object>} - A promise that resolves with the API response data.
 * @throws {Error} - Throws an error if the API call fails.
 */
export async function sendChatMessage(sessionId, chatInput) {
  if (!sessionId || !chatInput) {
    throw new Error('Session ID and chat input are required.');
  }

  try {
    const response = await axios.post(N8N_WEBHOOK_URL, {
      sessionId,
      chatInput,
    });

    // Basic validation of the expected response structure
    if (!response.data || typeof response.data.output !== 'object') {
      throw new Error('Received invalid response structure from API.');
    }

    return response.data; // Contains { output: { message: string, final_output: any } }
  } catch (error) {
    if (axios.isAxiosError(error)) {
      // Format and throw specific message for Axios errors
      throw new Error(`API Error: ${error.response?.status || 'Network Error'} - ${error.message}`);
    } else if (error instanceof Error && error.message === 'Received invalid response structure from API.') {
       // Re-throw the specific validation error message
       throw error;
    } else {
      // Throw a generic message for any other unexpected errors
      throw new Error('An unexpected error occurred while processing the API response.');
    }
  }
} 