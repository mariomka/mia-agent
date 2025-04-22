import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatMessage from './ChatMessage.vue';

describe('ChatMessage.vue', () => {
  it('renders AI message correctly (sent status)', () => {
    const message = {
      id: 'ai-1',
      sender: 'ai',
      text: 'Hello from AI',
      status: 'sent', // Explicitly sent
    };
    const wrapper = mount(ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-start');

    // Check bubble styling
    const bubbleDiv = wrapper.find('.rounded-lg');
    expect(bubbleDiv.exists()).toBe(true);
    expect(bubbleDiv.classes()).toContain('bg-gray-100');
    expect(bubbleDiv.classes()).toContain('text-gray-800');
    expect(bubbleDiv.classes()).toContain('rounded-lg');
    expect(bubbleDiv.classes()).toContain('text-lg');

    // Check no error stuff shown
    expect(wrapper.find('.border-t').exists()).toBe(false);
    expect(wrapper.find('button').exists()).toBe(false);
  });

  it('renders User message correctly (sent status)', () => {
    const message = {
      id: 'user-1',
      sender: 'user',
      text: 'Hello from User',
      status: 'sent',
    };
    const wrapper = mount(ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-end');

    // Check bubble styling
    const bubbleDiv = wrapper.find('.rounded-lg');
    expect(bubbleDiv.exists()).toBe(true);
    expect(bubbleDiv.classes()).toContain('bg-blue-100');
    expect(bubbleDiv.classes()).toContain('text-blue-900');
    expect(bubbleDiv.classes()).toContain('rounded-lg');
    expect(bubbleDiv.classes()).toContain('text-lg');

     // Check no error stuff shown
    expect(wrapper.find('.border-t').exists()).toBe(false);
    expect(wrapper.find('button').exists()).toBe(false);
  });

  it('renders User message with error status and retry button', () => {
    const errorMessage = 'Network failed';
    const message = {
      id: 'user-err-1',
      sender: 'user',
      text: 'This should fail',
      status: 'error',
      error: errorMessage,
    };
    const wrapper = mount(ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

    // Check bubble styling for error
    const bubbleDiv = wrapper.find('.rounded-lg');
    expect(bubbleDiv.classes()).toContain('bg-red-100');
    expect(bubbleDiv.classes()).toContain('text-red-700');

    // Check error message is displayed
    const errorDiv = wrapper.find('.border-t'); // Find error container
    expect(errorDiv.exists()).toBe(true);
    expect(errorDiv.text()).toContain('Message failed to send');

    // Check retry button exists
    const retryButton = errorDiv.find('button');
    expect(retryButton.exists()).toBe(true);
    expect(retryButton.text()).toBe('Retry');
  });

  it('emits retry event with message ID when retry button is clicked', async () => {
    const message = {
      id: 'user-err-2',
      sender: 'user',
      text: 'Failed message',
      status: 'error',
      error: 'Timeout',
    };
    const wrapper = mount(ChatMessage, { props: { message } });

    const retryButton = wrapper.find('button');
    await retryButton.trigger('click');

    expect(wrapper.emitted()).toHaveProperty('retry');
    expect(wrapper.emitted('retry')).toHaveLength(1);
    expect(wrapper.emitted('retry')[0]).toEqual([message.id]);
  });

  it('does not show sending indicator for AI messages or sent user messages', () => {
      const messageAI = { id: 'ai-sent', sender: 'ai', text: 'AI', status: 'sent' };
      const messageUserSent = { id: 'user-sent', sender: 'user', text: 'User Sent', status: 'sent' };

      const wrapperAI = mount(ChatMessage, { props: { message: messageAI } });
      const wrapperUser = mount(ChatMessage, { props: { message: messageUserSent } });

      expect(wrapperAI.find('.text-gray-400.italic').exists()).toBe(false);
      expect(wrapperUser.find('.text-gray-400.italic').exists()).toBe(false);
  });

  // Test text size separately
  it('uses correct text size', () => {
     const message = { id: 'any', sender: 'ai', text: 'Test text', status: 'sent' };
     const wrapper = mount(ChatMessage, { props: { message } });
     const bubbleDiv = wrapper.find('.rounded-lg');
     expect(bubbleDiv.exists()).toBe(true);
     expect(bubbleDiv.classes()).toContain('text-lg');
  });
}); 