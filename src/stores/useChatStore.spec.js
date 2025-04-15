import { setActivePinia, createPinia } from 'pinia';
import { describe, it, expect, beforeEach } from 'vitest';
import { useChatStore } from './useChatStore';

describe('Chat Store', () => {
  beforeEach(() => {
    // creates a fresh pinia and makes it active
    // so it's automatically picked up by any useStore()
    // call without needing to pass it to it:
    // `useStore(pinia)`
    setActivePinia(createPinia());
  });

  it('initializes a session correctly', () => {
    const store = useChatStore();

    // Check initial state (should be default)
    expect(store.sessionId).toBeNull();
    expect(store.messages).toEqual([]);
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();

    // Call the action
    store.initializeSession();

    // Check state after initialization
    expect(store.sessionId).toBeTypeOf('string');
    expect(store.sessionId).toHaveLength(36); // UUID length
    expect(store.messages).toEqual([]);
    expect(store.isLoading).toBe(false);
    expect(store.isInterviewEnded).toBe(false);
    expect(store.finalOutput).toBeNull();
  });

  // TODO: Add tests for sendMessage
  // TODO: Add tests for endInterview
}); 