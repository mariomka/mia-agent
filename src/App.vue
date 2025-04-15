<script setup>
import ChatView from './views/ChatView.vue';
</script>

<template>
  <ChatView />
</template>

<style>
/* Global styles - Tailwind is handling base styles */
/* Ensure html, body, and #app take full height for the chat layout */
html, body, #app {
  height: 100%;
  margin: 0;
  padding: 0;
  background-color: #f3f4f6; /* Default light gray background */
}
</style>
