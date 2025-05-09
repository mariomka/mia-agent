import { setActivePinia, createPinia } from 'pinia';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { useChatStore } from './useChatStore.js';
import * as apiService from '../services/apiService.js'; // Import like this for mocking

// Mock the apiService
vi.mock('../services/apiService');

// Mock usePage from @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({
    props: {
      interview: { id: 123 },
      sessionId: 'test-session-id',
      messages: []
    }
  }))
}));

describe('Chat Store', () => {
  beforeEach(() => {
    // creates a fresh pinia and makes it active
    setActivePinia(createPinia());
    // Reset mocks before each test
    vi.clearAllMocks();
  });

  it('initializes a session correctly and fetches initial message when empty', async () => {
    const store = useChatStore();
    
    // Mock the initializeChat function for the welcome message
    const welcomeMessage = "Welcome to the chat!";
    apiService.initializeChat.mockResolvedValue({
      output: { message: welcomeMessage, final_output: null }
    });

    // Check initial state from mocked props
    expect(store.sessionId).toBe('test-session-id');
    expect(store.interviewId).toBe(123);
    expect(store.messages).toEqual([]);
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();
    expect(store.error).toBeNull();

    // Call the action - need to await since it now makes an API call
    await store.initializeSession();

    // Verify initial API call was made
    expect(apiService.initializeChat).toHaveBeenCalledWith('test-session-id', 123);
    
    // Check state after initialization
    expect(store.sessionId).toBe('test-session-id');
    expect(store.messages).toHaveLength(1); // Should have welcome message
    expect(store.messages[0]).toMatchObject({
      sender: 'ai',
      text: welcomeMessage,
      status: 'sent'
    });
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();
    expect(store.error).toBeNull();
  });

  it('fetches initial AI message', async () => {
    const store = useChatStore();
    const welcomeMessage = "Welcome to the interview!";
    
    apiService.initializeChat.mockResolvedValue({
      output: { message: welcomeMessage, final_output: null }
    });
    
    await store.fetchInitialAIMessage();
    
    expect(apiService.initializeChat).toHaveBeenCalledWith('test-session-id', 123);
    expect(store.messages).toHaveLength(1);
    expect(store.messages[0]).toMatchObject({
      sender: 'ai',
      text: welcomeMessage,
      status: 'sent'
    });
    expect(store.isLoading).toBe(false);
  });

  it('adds user message and calls API on sendMessage', async () => {
    const store = useChatStore();
    // Initialize with a welcome message already
    const welcomeMessage = "Welcome to the chat!";
    store.messages = [{
      id: 'welcome-msg',
      sender: 'ai',
      text: welcomeMessage,
      status: 'sent'
    }];
    
    const userMessage = 'Hello there';
    const aiMessage = 'General Kenobi!';
    // Use messages array format for compatibility with updated code
    const mockApiResponse = { output: { messages: [aiMessage], final_output: null } };
    apiService.sendChatMessage.mockResolvedValue(mockApiResponse);

    await store.sendMessage(userMessage);

    expect(store.messages).toHaveLength(3); // Welcome + user + ai response
    expect(store.messages[1]).toMatchObject({ sender: 'user', text: userMessage, status: 'sent' });
    expect(store.messages[2]).toMatchObject({ sender: 'ai', text: aiMessage, status: 'sent' });
    expect(apiService.sendChatMessage).toHaveBeenCalledTimes(1);
    expect(store.isLoading).toBe(false);
  });

  it('handles API error during sendMessage by updating message status', async () => {
    const store = useChatStore();
    // Initialize with a welcome message already
    const welcomeMessage = "Welcome to the chat!";
    store.messages = [{
      id: 'welcome-msg',
      sender: 'ai',
      text: welcomeMessage,
      status: 'sent'
    }];
    
    const userMessage = 'Trigger error';
    const errorMessage = 'API Error: 500';
    apiService.sendChatMessage.mockRejectedValue(new Error(errorMessage));

    await store.sendMessage(userMessage);

    expect(store.messages).toHaveLength(2); // Welcome + error user message
    expect(store.messages[1]).toMatchObject({
      sender: 'user',
      text: userMessage,
      status: 'error',
      error: errorMessage,
    });
    expect(store.isLoading).toBe(false);
  });

  it('ends the interview when final_output is received', async () => {
    const store = useChatStore();
    // Initialize with a welcome message already
    const welcomeMessage = "Welcome to the chat!";
    store.messages = [{
      id: 'welcome-msg',
      sender: 'ai',
      text: welcomeMessage,
      status: 'sent'
    }];
    
    const userMessage = 'Final question';
    const finalData = { summary: 'Interview complete' };
    const aiResponse = 'Thanks for your time.';
    const mockApiResponse = {
      output: { messages: [aiResponse], final_output: finalData },
    };
    apiService.sendChatMessage.mockResolvedValue(mockApiResponse);

    await store.sendMessage(userMessage);

    // Check messages
    expect(store.messages).toHaveLength(3); // Welcome + User + AI
    expect(store.messages[2].text).toBe(aiResponse);

    // Check final state
    expect(store.isLoading).toBe(false); // Loading should be off
    expect(store.isInterviewEnded).toBe(true);
    expect(store.finalOutput).toEqual(finalData);
    expect(store.error).toBeNull();

    // Check user message status
    expect(store.messages[1]).toMatchObject({ status: 'sent' });
  });

  it('does not send message if already loading', async () => {
    const store = useChatStore();
    store.isLoading = true; // Manually set loading state

    await store.sendMessage('Test while loading');

    expect(apiService.sendChatMessage).not.toHaveBeenCalled();
    expect(store.messages).toHaveLength(0); // No message should be added
  });

  it('does not send message if interview has ended', async () => {
    const store = useChatStore();
    store.isInterviewEnded = true; // Manually set ended state

    await store.sendMessage('Test after end');

    expect(apiService.sendChatMessage).not.toHaveBeenCalled();
    expect(store.messages).toHaveLength(0); // No message should be added
  });

  // Test the endInterview action directly
  it('endInterview action sets state correctly', () => {
    const store = useChatStore();
    store.isLoading = true; // Simulate loading before end
    const finalData = { result: 'done' };

    store.endInterview(finalData);

    expect(store.isInterviewEnded).toBe(true);
    expect(store.finalOutput).toEqual(finalData);
    expect(store.isLoading).toBe(false);
  });

  it('retryFailedMessage calls sendMessage again for an error message', async () => {
    const store = useChatStore();
    
    // Setup initial message in error state
    const errorMessage = { 
      id: 'test-error-msg', 
      sender: 'user', 
      text: 'Retry me', 
      status: 'error', 
      error: 'Previous error'
    };
    store.messages.push(errorMessage);
    
    // Setup mock response for retry
    const aiResponse = "Here's the retry response";
    apiService.sendChatMessage.mockResolvedValue({ 
      output: { message: aiResponse, final_output: null } 
    });

    await store.retryFailedMessage('test-error-msg');

    // Check that the error message was updated
    expect(store.messages[0].status).toBe('sent');
    expect(store.messages[0].error).toBeUndefined();
    
    // Check that AI response was added
    expect(store.messages).toHaveLength(2);
    expect(store.messages[1].sender).toBe('ai');
    expect(store.messages[1].text).toBe(aiResponse);
    
    // Check API was called with correct data
    expect(apiService.sendChatMessage).toHaveBeenCalledWith(
      'test-session-id', 'Retry me', 123
    );
  });

  it('retryFailedMessage does nothing for non-error or non-user messages', async () => {
    const store = useChatStore();
    
    // Add sent user message (not in error state)
    store.messages.push({ 
      id: 'ok-msg', 
      sender: 'user', 
      text: 'Already sent', 
      status: 'sent' 
    });
    
    // Add AI message
    store.messages.push({ 
      id: 'ai-msg', 
      sender: 'ai', 
      text: 'AI response', 
      status: 'sent' 
    });

    await store.retryFailedMessage('ok-msg'); // Should do nothing - not in error state
    await store.retryFailedMessage('ai-msg'); // Should do nothing - not a user message

    // API should not have been called
    expect(apiService.sendChatMessage).not.toHaveBeenCalled();
    
    // Messages should be unchanged
    expect(store.messages).toHaveLength(2);
    expect(store.messages[0].status).toBe('sent');
  });

  it('does NOT automatically retry if the last message is from AI', async () => {
    const store = useChatStore();
    
    // Add messages with AI as the last one
    store.messages = [
      { id: 'user-msg', sender: 'user', text: 'User message', status: 'sent' },
      { id: 'ai-msg', sender: 'ai', text: 'AI response', status: 'sent' }
    ];
    
    // Mock the retryFailedMessage method
    store.retryFailedMessage = vi.fn();
    
    // Initialize session - should not call fetchInitialAIMessage since we have messages
    store.initializeSession();
    
    // Check retry was not called
    expect(store.retryFailedMessage).not.toHaveBeenCalled();
    // Check that initialize didn't call API since messages not empty
    expect(apiService.initializeChat).not.toHaveBeenCalled();
  });
});
