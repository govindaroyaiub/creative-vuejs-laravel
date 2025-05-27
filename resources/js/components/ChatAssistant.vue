<template>
  <div class="chat-container">
    <h2>AI Chat Assistant</h2>
    <div class="chat-messages">
      <div v-for="(message, index) in messages" :key="index" :class="['message', message.sender]">
        {{ message.text }}
      </div>
    </div>
    <div class="chat-input">
      <input v-model="userQuery" @keyup.enter="sendMessage" placeholder="Ask me anything..." :disabled="loading" />
      <button @click="sendMessage" :disabled="loading">
        {{ loading ? 'Sending...' : 'Send' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios'; // Correct import for Axios

const userQuery = ref('');
const messages = ref([]);
const loading = ref(false);

const sendMessage = async () => {
  if (!userQuery.value.trim()) return;

  // Add user message to display immediately
  messages.value.push({ sender: 'user', text: userQuery.value });
  const queryToSend = userQuery.value;
  userQuery.value = ''; // Clear input
  loading.value = true;

  try {
    // Make a direct POST request using Axios to your Laravel API endpoint
    const response = await axios.post('/api/chat-assistant', { query: queryToSend });

    // Assuming your Laravel controller returns JSON like:
    // return response()->json(['reply' => $response->text()]);
    const aiReply = response.data.reply || 'No specific answer received.';
    messages.value.push({ sender: 'ai', text: aiReply });

  } catch (error) {
    console.error('API Error:', error);
    // Display a user-friendly error message
    messages.value.push({ sender: 'ai', text: 'Error: Could not get a response from the assistant. Please try again.' });
  } finally {
    loading.value = false; // Always stop loading, regardless of success or failure
  }
};
</script>

<style scoped>
.chat-container {
  max-width: 600px;
  margin: 20px auto;
  border: 1px solid #ccc;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 500px;
}

.chat-messages {
  flex-grow: 1;
  padding: 15px;
  overflow-y: auto;
  background-color: #f9f9f9;
}

.message {
  padding: 8px 12px;
  margin-bottom: 10px;
  border-radius: 15px;
  max-width: 80%;
}

.message.user {
  background-color: #e0f7fa;
  align-self: flex-end;
  margin-left: auto;
  text-align: right;
}

.message.ai {
  background-color: #fff3e0;
  align-self: flex-start;
  margin-right: auto;
}

.chat-input {
  display: flex;
  padding: 15px;
  border-top: 1px solid #eee;
}

.chat-input input {
  flex-grow: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
  margin-right: 10px;
}

.chat-input button {
  padding: 10px 15px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.chat-input button:disabled {
  background-color: #a0cffc;
  cursor: not-allowed;
}
</style>