import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatMessage from './ChatMessage.vue';
import { mountWithI18n } from '../testUtils/i18nTestHelper';

describe('ChatMessage.vue', () => {
  it('renders AI message correctly (sent status)', () => {
    const message = {
      id: 'ai-1',
      sender: 'ai',
      text: 'Hello from AI',
      status: 'sent', // Explicitly sent
    };
    const wrapper = mountWithI18n(mount, ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-start');

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
    const wrapper = mountWithI18n(mount, ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-end');

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
    const wrapper = mountWithI18n(mount, ChatMessage, { props: { message } });

    expect(wrapper.text()).toContain(message.text);

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
    const wrapper = mountWithI18n(mount, ChatMessage, { props: { message } });

    const retryButton = wrapper.find('button');
    await retryButton.trigger('click');

    expect(wrapper.emitted()).toHaveProperty('retry');
    expect(wrapper.emitted('retry')).toHaveLength(1);
    expect(wrapper.emitted('retry')[0]).toEqual([message.id]);
  });


});
