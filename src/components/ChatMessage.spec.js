import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ChatMessage from './ChatMessage.vue';

describe('ChatMessage.vue', () => {
  it('renders AI message correctly (left aligned, light gray bubble)', () => {
    const message = {
      id: 'ai-1',
      sender: 'ai',
      text: 'Hello from AI',
    };
    const wrapper = mount(ChatMessage, {
      props: { message },
    });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-start');

    // Check bubble styling - Find by a unique class like rounded-lg
    const bubbleDiv = wrapper.find('.rounded-lg');
    expect(bubbleDiv.exists()).toBe(true);
    expect(bubbleDiv.classes()).toContain('bg-gray-100');
    expect(bubbleDiv.classes()).toContain('text-gray-800');
    expect(bubbleDiv.classes()).toContain('text-xl');
    expect(bubbleDiv.classes()).toContain('rounded-lg');
  });

  it('renders User message correctly (right aligned, pale blue bubble)', () => {
    const message = {
      id: 'user-1',
      sender: 'user',
      text: 'Hello from User',
    };
    const wrapper = mount(ChatMessage, {
      props: { message },
    });

    expect(wrapper.text()).toContain(message.text);

    // Check outer alignment
    const outerDiv = wrapper.find('.flex');
    expect(outerDiv.classes()).toContain('justify-end');

    // Check bubble styling - Find by rounded-lg
    const bubbleDiv = wrapper.find('.rounded-lg');
    expect(bubbleDiv.exists()).toBe(true);
    expect(bubbleDiv.classes()).toContain('bg-blue-100');
    expect(bubbleDiv.classes()).toContain('text-blue-900');
    expect(bubbleDiv.classes()).toContain('text-xl');
    expect(bubbleDiv.classes()).toContain('rounded-lg');
  });

  it('uses large text size', () => {
    const message = { id: 'any', sender: 'ai', text: 'Test text' };
    const wrapper = mount(ChatMessage, { props: { message } });
    const bubbleDiv = wrapper.find('.rounded-lg'); // Find bubble
    expect(bubbleDiv.exists()).toBe(true);
    expect(bubbleDiv.classes()).toContain('text-xl');
  });
}); 