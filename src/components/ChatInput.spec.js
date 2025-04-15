import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatInput from './ChatInput.vue';

describe('ChatInput.vue', () => {
  it('renders textarea and button', () => {
    const wrapper = mount(ChatInput);
    expect(wrapper.find('textarea').exists()).toBe(true);
    expect(wrapper.find('button').exists()).toBe(true);
    expect(wrapper.find('button').text()).toBe('Send');
  });

  it('enables button only when textarea has text', async () => {
    const wrapper = mount(ChatInput);
    const textarea = wrapper.find('textarea');
    const button = wrapper.find('button');

    // Initially button should be disabled
    expect(button.attributes('disabled')).toBeDefined();

    // Enter text
    await textarea.setValue('Some text');
    expect(button.attributes('disabled')).toBeUndefined();

    // Clear text
    await textarea.setValue('   '); // Whitespace only
    expect(button.attributes('disabled')).toBeDefined();

    // Enter text again
    await textarea.setValue('More text');
    expect(button.attributes('disabled')).toBeUndefined();
  });

  it('emits sendMessage event on button click', async () => {
    const wrapper = mount(ChatInput);
    const textarea = wrapper.find('textarea');
    const button = wrapper.find('button');
    const message = 'Test message';

    await textarea.setValue(message);
    await button.trigger('click');

    // Check if event was emitted
    expect(wrapper.emitted()).toHaveProperty('sendMessage');
    expect(wrapper.emitted('sendMessage')).toHaveLength(1);
    expect(wrapper.emitted('sendMessage')[0]).toEqual([message]);

    // Check if textarea was cleared
    expect(textarea.element.value).toBe('');
  });

  it('emits sendMessage event on Enter key press (not Shift+Enter)', async () => {
    const wrapper = mount(ChatInput);
    const textarea = wrapper.find('textarea');
    const message = 'Enter key test';

    await textarea.setValue(message);

    // Simulate Enter key press
    await textarea.trigger('keydown', { key: 'Enter' });

    expect(wrapper.emitted()).toHaveProperty('sendMessage');
    expect(wrapper.emitted('sendMessage')[0]).toEqual([message]);
    expect(textarea.element.value).toBe('');

    // Simulate Shift+Enter key press (should not emit)
    const shiftEnterMessage = 'Shift Enter';
    await textarea.setValue(shiftEnterMessage);
    await textarea.trigger('keydown', { key: 'Enter', shiftKey: true });
    expect(wrapper.emitted('sendMessage')).toHaveLength(1); // Should still be 1

    // Manually update value with newline to reflect expected browser behavior
    await textarea.setValue(shiftEnterMessage + '\n');
    expect(textarea.element.value).toContain('\n'); // Check if newline was added
  });

  it('disables input and button when isLoading is true', async () => {
    const wrapper = mount(ChatInput, {
      props: {
        isLoading: false,
      },
    });
    const textarea = wrapper.find('textarea');
    const button = wrapper.find('button');

    // Initially enabled
    expect(textarea.attributes('disabled')).toBeUndefined();
    expect(button.attributes('disabled')).toBeDefined(); // Disabled due to no text
    await textarea.setValue('text');
    expect(button.attributes('disabled')).toBeUndefined(); // Enabled with text

    // Set isLoading to true
    await wrapper.setProps({ isLoading: true });

    expect(textarea.attributes('disabled')).toBeDefined();
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.text()).toBe('...'); // Check for loading indicator

    // Try clicking button while loading (should not emit)
    await button.trigger('click');
    expect(wrapper.emitted('sendMessage')).toBeUndefined();

     // Set isLoading back to false
     await wrapper.setProps({ isLoading: false });
     expect(textarea.attributes('disabled')).toBeUndefined();
     expect(button.attributes('disabled')).toBeUndefined(); // Enabled because text exists
     expect(button.text()).toBe('Send');
  });

  // Add more tests for edge cases or specific behaviors if needed
}); 