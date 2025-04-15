import { setActivePinia, createPinia } from 'pinia';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { useChatStore } from './useChatStore';
import * as apiService from '../services/apiService'; // Import like this for mocking

// Mock the apiService
vi.mock('../services/apiService');

describe('Chat Store', () => {
  beforeEach(() => {
    // creates a fresh pinia and makes it active
    // so it's automatically picked up by any useStore()
    // call without needing to pass it to it:
    // `useStore(pinia)`
    setActivePinia(createPinia());
    // Reset mocks before each test
    vi.clearAllMocks();
  });

  it('initializes a session correctly', () => {
    const store = useChatStore();

    // Check initial state (should be default)
    expect(store.sessionId).toBeNull();
    expect(store.messages).toEqual([]);
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();
    expect(store.error).toBeNull();

    // Call the action
    store.initializeSession();

    // Check state after initialization
    expect(store.sessionId).toBeTypeOf('string');
    expect(store.sessionId).toHaveLength(36); // UUID length
    expect(store.messages).toEqual([]);
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();
    expect(store.error).toBeNull();
  });

  it('adds user message and calls API on sendMessage', async () => {
    const store = useChatStore();
    store.initializeSession(); // Need a session ID
    const userMessage = 'Hello there';
    const mockSessionId = store.sessionId;

    const mockApiResponse = {
      output: { message: 'General Kenobi!', final_output: null },
    };
    apiService.sendChatMessage.mockResolvedValue(mockApiResponse);

    await store.sendMessage(userMessage);

    // Check if user message was added
    expect(store.messages).toHaveLength(2); // User + AI
    expect(store.messages[0].sender).toBe('user');
    expect(store.messages[0].text).toBe(userMessage);
    expect(store.messages[0].id).toBeTypeOf('string');

    // Check if API was called correctly
    expect(apiService.sendChatMessage).toHaveBeenCalledTimes(1);
    expect(apiService.sendChatMessage).toHaveBeenCalledWith(mockSessionId, userMessage);

    // Check if AI message was added
    expect(store.messages[1].sender).toBe('ai');
    expect(store.messages[1].text).toBe(mockApiResponse.output.message);
    expect(store.messages[1].id).toBeTypeOf('string');

    // Check final state
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.error).toBeNull();
  });

  it('handles API error during sendMessage', async () => {
    const store = useChatStore();
    store.initializeSession();
    const userMessage = 'Something went wrong';
    const errorMessage = 'API Error: 500 - Server Error';
    apiService.sendChatMessage.mockRejectedValue(new Error(errorMessage));

    await store.sendMessage(userMessage);

    // Check if user message was added
    expect(store.messages).toHaveLength(2); // User + AI Error Message
    expect(store.messages[0].sender).toBe('user');
    expect(store.messages[0].text).toBe(userMessage);

    // Check if error message was added to chat
    expect(store.messages[1].sender).toBe('ai');
    expect(store.messages[1].text).toContain(errorMessage);

    // Check final state
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false); // Should not end interview on error by default
    expect(store.error).toBe(errorMessage);
  });

  it('ends the interview when final_output is received', async () => {
    const store = useChatStore();
    store.initializeSession();
    const userMessage = 'Final question';
    const finalData = { summary: 'Interview complete' };
    const mockApiResponse = {
      output: { message: 'Thanks for your time.', final_output: finalData },
    };
    apiService.sendChatMessage.mockResolvedValue(mockApiResponse);

    await store.sendMessage(userMessage);

    // Check messages
    expect(store.messages).toHaveLength(2); // User + AI
    expect(store.messages[1].text).toBe(mockApiResponse.output.message);

    // Check final state
    expect(store.isLoading).toBe(false); // Loading should be off
    expect(store.isInterviewEnded).toBe(true);
    expect(store.finalOutput).toEqual(finalData);
    expect(store.error).toBeNull();
  });

  it('does not send message if already loading', async () => {
    const store = useChatStore();
    store.initializeSession();
    store.isLoading = true; // Manually set loading state

    await store.sendMessage('Test while loading');

    expect(apiService.sendChatMessage).not.toHaveBeenCalled();
    expect(store.messages).toHaveLength(0); // No message should be added
  });

  it('does not send message if interview has ended', async () => {
    const store = useChatStore();
    store.initializeSession();
    store.isInterviewEnded = true; // Manually set ended state

    await store.sendMessage('Test after end');

    expect(apiService.sendChatMessage).not.toHaveBeenCalled();
    expect(store.messages).toHaveLength(0); // No message should be added
  });

  // Test the endInterview action directly
  it('endInterview action sets state correctly', () => {
    const store = useChatStore();
    store.initializeSession();
    store.isLoading = true; // Simulate loading before end
    const finalData = { result: 'done' };

    store.endInterview(finalData);

    expect(store.isInterviewEnded).toBe(true);
    expect(store.finalOutput).toEqual(finalData);
    expect(store.isLoading).toBe(false); // Ensure loading is turned off
    expect(store.error).toBeNull();
  });
}); 