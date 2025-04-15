import { setActivePinia, createPinia } from 'pinia';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { useChatStore } from './useChatStore';
import * as apiService from '../services/apiService'; // Import like this for mocking

// Mock the apiService
vi.mock('../services/apiService');

// Mock sessionStorage
const sessionStorageMock = (() => {
  let store = {};
  return {
    getItem: vi.fn((key) => store[key] || null),
    setItem: vi.fn((key, value) => {
      store[key] = value.toString();
    }),
    removeItem: vi.fn((key) => {
      delete store[key];
    }),
    clear: vi.fn(() => {
      store = {};
    }),
  };
})();
Object.defineProperty(window, 'sessionStorage', {
  value: sessionStorageMock,
});

describe('Chat Store', () => {
  const MESSAGES_STORAGE_KEY = 'miaMessages';
  const SESSION_ID_STORAGE_KEY = 'miaSessionId';

  beforeEach(() => {
    // creates a fresh pinia and makes it active
    // so it's automatically picked up by any useStore()
    // call without needing to pass it to it:
    // `useStore(pinia)`
    setActivePinia(createPinia());
    // Reset mocks before each test
    vi.clearAllMocks();
    sessionStorageMock.clear();
  });

  it('initializes a new session and stores ID/empty messages', () => {
    const store = useChatStore();
    store.initializeSession();

    expect(sessionStorageMock.getItem).toHaveBeenCalledWith(SESSION_ID_STORAGE_KEY);
    expect(store.sessionId).toBeTypeOf('string');
    expect(sessionStorageMock.setItem).toHaveBeenCalledWith(SESSION_ID_STORAGE_KEY, store.sessionId);
    // Check messages are empty and stored
    expect(store.messages).toEqual([]);
    expect(sessionStorageMock.setItem).toHaveBeenCalledWith(MESSAGES_STORAGE_KEY, JSON.stringify([]));
  });

  it('reuses existing session ID and loads stored messages', () => {
    const existingId = 'existing-uuid-123';
    const existingMessages = [{ id: 'msg1', sender: 'ai', text: 'Existing msg' }];
    sessionStorageMock.setItem(SESSION_ID_STORAGE_KEY, existingId);
    sessionStorageMock.setItem(MESSAGES_STORAGE_KEY, JSON.stringify(existingMessages));
    const store = useChatStore();

    // Clear setItem mock calls from setup before running the action
    sessionStorageMock.setItem.mockClear();

    store.initializeSession();

    // Check that getItem was called for both keys
    expect(sessionStorageMock.getItem).toHaveBeenCalledWith(SESSION_ID_STORAGE_KEY);
    expect(sessionStorageMock.getItem).toHaveBeenCalledWith(MESSAGES_STORAGE_KEY);
    // Check state
    expect(store.sessionId).toBe(existingId);
    expect(store.messages).toEqual(existingMessages);

    // Check setItem calls *during* initializeSession
    const setItemCalls = sessionStorageMock.setItem.mock.calls;
    // Verify messages *were* stored (by updateStoredMessages)
    expect(setItemCalls.some(call => call[0] === MESSAGES_STORAGE_KEY)).toBe(true);
    // Verify session ID was *not* stored
    expect(setItemCalls.some(call => call[0] === SESSION_ID_STORAGE_KEY)).toBe(false);
  });

  it('handles invalid stored messages by starting fresh', () => {
    const existingId = 'existing-uuid-456';
    sessionStorageMock.setItem(SESSION_ID_STORAGE_KEY, existingId);
    sessionStorageMock.setItem(MESSAGES_STORAGE_KEY, 'this is not json'); // Invalid data
    const store = useChatStore();

    store.initializeSession();

    expect(store.sessionId).toBe(existingId);
    expect(store.messages).toEqual([]); // Should be empty
    expect(sessionStorageMock.removeItem).toHaveBeenCalledWith(MESSAGES_STORAGE_KEY); // Should remove invalid data
    // Should store the now-empty message array
    expect(sessionStorageMock.setItem).toHaveBeenCalledWith(MESSAGES_STORAGE_KEY, JSON.stringify([]));
  });

  it('clearSession removes session ID and messages from storage', () => {
    const store = useChatStore();
    store.initializeSession(); // Put items in storage
    // Clear mocks from init
    vi.clearAllMocks();

    store.clearSession();

    expect(sessionStorageMock.removeItem).toHaveBeenCalledTimes(2);
    expect(sessionStorageMock.removeItem).toHaveBeenCalledWith(SESSION_ID_STORAGE_KEY);
    expect(sessionStorageMock.removeItem).toHaveBeenCalledWith(MESSAGES_STORAGE_KEY);
  });

  it('sendMessage updates stored messages after adding user and AI message', async () => {
    const store = useChatStore();
    store.initializeSession(); // Stores initial empty messages
    const userMessage = 'Test storage update';
    const aiMessage = 'Storage updated';
    const mockApiResponse = { output: { message: aiMessage, final_output: null } };
    apiService.sendChatMessage.mockResolvedValue(mockApiResponse);
    vi.clearAllMocks(); // Clear mocks from initialization

    await store.sendMessage(userMessage);

    // Check the setItem calls by parsing the stored JSON
    const calls = sessionStorageMock.setItem.mock.calls;
    expect(calls).toHaveLength(2); // after user, after AI

    // Check first call (after user message)
    const storedAfterUser = JSON.parse(calls[0][1]);
    expect(storedAfterUser).toEqual([
      expect.objectContaining({ sender: 'user', text: userMessage })
    ]);

    // Check second call (after AI message)
    const storedAfterAI = JSON.parse(calls[1][1]);
    expect(storedAfterAI).toEqual([
      expect.objectContaining({ sender: 'user', text: userMessage }),
      expect.objectContaining({ sender: 'ai', text: aiMessage })
    ]);
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