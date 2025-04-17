import { describe, it, expect, vi, afterEach } from 'vitest';
import axios from 'axios';
import { sendChatMessage } from './apiService.js';

// Mock axios
vi.mock('axios');

describe('API Service - sendChatMessage', () => {
  const mockSessionId = 'test-session-id';
  const mockChatInput = 'Hello, AI!';
  const mockInterviewId = 123;
  const CHAT_URL = 'http://127.0.0.1:8000/chat';

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should send a message successfully and return data', async () => {
    const mockResponseData = {
      output: {
        message: 'Hello, User!',
        final_output: null,
      },
    };
    axios.post.mockResolvedValue({ data: mockResponseData });

    const result = await sendChatMessage(mockSessionId, mockChatInput, mockInterviewId);

    expect(axios.post).toHaveBeenCalledTimes(1);
    expect(axios.post).toHaveBeenCalledWith(CHAT_URL, {
      sessionId: mockSessionId,
      chatInput: mockChatInput,
      interviewId: mockInterviewId,
    });
    expect(result).toEqual(mockResponseData);
  });

  it('should throw an error if session ID is missing', async () => {
    await expect(sendChatMessage(null, mockChatInput, mockInterviewId)).rejects.toThrow(
      'Session ID, chat input, and interview ID are required.'
    );
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('should throw an error if chat input is missing', async () => {
    await expect(sendChatMessage(mockSessionId, '', mockInterviewId)).rejects.toThrow(
      'Session ID, chat input, and interview ID are required.'
    );
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('should throw an error if interview ID is missing', async () => {
    await expect(sendChatMessage(mockSessionId, mockChatInput, null)).rejects.toThrow(
      'Session ID, chat input, and interview ID are required.'
    );
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('should throw an error if API call fails (network error)', async () => {
    const networkError = new Error('Network Error');
    networkError.isAxiosError = true; // Mock Axios error properties
    axios.post.mockRejectedValue(networkError);

    await expect(sendChatMessage(mockSessionId, mockChatInput, mockInterviewId)).rejects.toThrow(
      'An unexpected error occurred while processing the API response.'
    );
    expect(axios.post).toHaveBeenCalledTimes(1);
  });

  it('should throw an error if API returns non-2xx status', async () => {
    const apiError = new Error('Request failed with status code 500');
    apiError.isAxiosError = true;
    apiError.response = { status: 500 };
    axios.post.mockRejectedValue(apiError);

    await expect(sendChatMessage(mockSessionId, mockChatInput, mockInterviewId)).rejects.toThrow(
      'An unexpected error occurred while processing the API response.'
    );
    expect(axios.post).toHaveBeenCalledTimes(1);
  });

   it('should throw an error if API response structure is invalid', async () => {
    const invalidResponseData = { message: 'Missing output field' };
    axios.post.mockResolvedValue({ data: invalidResponseData });

    await expect(sendChatMessage(mockSessionId, mockChatInput, mockInterviewId)).rejects.toThrow(
      'Received invalid response structure from API.'
    );
    expect(axios.post).toHaveBeenCalledTimes(1);
  });

  it('should throw an error for non-axios errors during the request', async () => {
    const genericError = new Error('Something unexpected happened');
    axios.post.mockRejectedValue(genericError); // Simulate non-Axios error

    await expect(sendChatMessage(mockSessionId, mockChatInput, mockInterviewId)).rejects.toThrow(
      'An unexpected error occurred while processing the API response.'
    );
    expect(axios.post).toHaveBeenCalledTimes(1);
  });
});
