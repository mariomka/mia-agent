import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatMessage from './ChatMessage.vue';

describe('ChatMessage.vue', () => {
  it('renders AI message correctly (left aligned)', () => {
    const message = {
      id: 'ai-1',
      sender: 'ai',
      text: 'Hello from AI',
    };
    const wrapper = mount(ChatMessage, {
      props: { message },
    });

    // Check text content
    expect(wrapper.text()).toContain(message.text);

    // Check classes for AI message (left alignment)
    const outerDiv = wrapper.find('div'); // The outer div
    expect(outerDiv.classes()).toContain('justify-start');

    // Correctly target the inner div with text classes
    const innerDiv = wrapper.find('.text-lg'); // Find by a unique class on the inner div
    expect(innerDiv.exists()).toBe(true);
    expect(innerDiv.classes()).toContain('text-left');
    // Ensure bubble classes are NOT present as per plan
    expect(innerDiv.classes()).not.toContain('bg-white');
    expect(innerDiv.classes()).not.toContain('rounded-lg');
  });

  it('renders User message correctly (right aligned)', () => {
    const message = {
      id: 'user-1',
      sender: 'user',
      text: 'Hello from User',
    };
    const wrapper = mount(ChatMessage, {
      props: { message },
    });

    // Check text content
    expect(wrapper.text()).toContain(message.text);

    // Check classes for User message (right alignment)
    const outerDiv = wrapper.find('div'); // The outer div
    expect(outerDiv.classes()).toContain('justify-end');

    // Correctly target the inner div with text classes
    const innerDiv = wrapper.find('.text-lg'); // Find by a unique class on the inner div
    expect(innerDiv.exists()).toBe(true);
    expect(innerDiv.classes()).toContain('text-right');
    // Ensure bubble classes are NOT present
    expect(innerDiv.classes()).not.toContain('bg-blue-500');
    expect(innerDiv.classes()).not.toContain('text-white');
    expect(innerDiv.classes()).not.toContain('rounded-lg');
  });

  it('uses large text size', () => {
     const message = { id: 'any', sender: 'ai', text: 'Test text' };
     const wrapper = mount(ChatMessage, { props: { message } });
     const innerDiv = wrapper.find('.text-lg'); // Use the same selector
     expect(innerDiv.exists()).toBe(true);
     expect(innerDiv.classes()).toContain('text-lg');
  });
}); 