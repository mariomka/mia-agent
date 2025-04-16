import axios from 'axios';

const CHAT_URL = 'http://127.0.0.1:8000/chat';

/**
 * Sends a message to the chat API endpoint.
 * @param {string} sessionId - The unique session identifier.
 * @param {string} chatInput - The user's message text.
 * @param {number} interviewId - The ID of the interview.
 * @returns {Promise<object>} - A promise that resolves with the API response data.
 * @throws {Error} - Throws an error if the API call fails.
 */
export async function sendChatMessage(sessionId, chatInput, interviewId) {
  if (!sessionId || !chatInput || !interviewId) {
    throw new Error('Session ID, chat input, and interview ID are required.');
  }

  try {
    const response = await axios.post(CHAT_URL, {
      sessionId,
      chatInput,
      interviewId,
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
