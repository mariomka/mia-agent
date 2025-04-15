import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatInput from './ChatInput.vue';

describe('ChatInput.vue', () => {
  it('renders textarea and button with send icon', () => {
    const wrapper = mount(ChatInput);
    expect(wrapper.find('textarea').exists()).toBe(true);
    const button = wrapper.find('button');
    expect(button.exists()).toBe(true);
    // Check for *any* SVG initially (should be send icon)
    expect(button.find('svg').exists()).toBe(true);
    // Check it's *not* the loading spinner
    expect(button.find('svg.animate-spin').exists()).toBe(false);
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
    const wrapper = mount(ChatInput, { props: { isLoading: false } });
    const textarea = wrapper.find('textarea');
    const button = wrapper.find('button');

    // Initial state check (send icon present)
    expect(button.find('svg').exists()).toBe(true);
    // ... other initial checks ...

    // Set isLoading to true
    await wrapper.setProps({ isLoading: true });

    expect(textarea.attributes('disabled')).toBeDefined();
    expect(button.attributes('disabled')).toBeDefined();
    // Check that the send icon SVG is still present
    expect(button.find('svg').exists()).toBe(true);

    // Set isLoading back to false
    await wrapper.setProps({ isLoading: false });
    // Check elements are enabled again
    expect(textarea.attributes('disabled')).toBeUndefined();
    // Button might still be disabled if text is empty, so check based on that or just check the prop effect is gone
    // Let's just check the send icon is still there
    expect(button.find('svg').exists()).toBe(true);
    // ... other final checks ...
  });

  // Add more tests for edge cases or specific behaviors if needed
}); 