<template>
  <div class="fixed bottom-6 right-6 z-50">
    <!-- Chat Box -->
    <div
      v-if="open"
      class="w-[32rem] rounded-xl shadow-2xl bg-white dark:bg-gray-800 border dark:border-gray-700 p-4 flex flex-col"
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">💬 AI Assistant</h2>
        <button
          @click="toggleOpen"
          class="text-lg font-bold text-gray-400 hover:text-red-500 transition"
        >
          ×
        </button>
      </div>

      <!-- Chat Log -->
      <div
        class="flex-1 overflow-y-auto border rounded p-3 space-y-3 text-sm bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
        v-html="chatLog"
      ></div>

      <!-- Input -->
      <form @submit.prevent="submit" class="mt-3 flex items-center gap-2">
        <input
          v-model="message"
          class="flex-1 rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
          placeholder="Ask anything..."
        />
        <button
          :disabled="!message.trim()"
          class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded transition disabled:opacity-50"
        >
          <SendHorizontal class="h-5 w-5" />
        </button>
      </form>
    </div>

    <!-- Open Button -->
    <button
      v-if="!open"
      @click="toggleOpen"
      class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-full shadow-lg transition"
    >
      💬
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { SendHorizontal } from 'lucide-vue-next';

const open = ref(false);
const message = ref('');
const chatLog = ref('👋 <b>Hi, how can I help you today?</b><br><br>');

const toggleOpen = () => {
  open.value = !open.value;
};

const submit = async () => {
  if (!message.value.trim()) return;

  const userMessage = message.value.trim();
  chatLog.value += `<div><b>You:</b> ${userMessage}</div>`;
  message.value = '';

  try {
    const res = await axios.post('/api/assistant', { message: userMessage });
    chatLog.value += `<div class="text-green-600 dark:text-green-400"><b>Assistant:</b> ${res.data.answer}</div>`;
  } catch {
    chatLog.value += `<div class="text-red-600"><b>Error:</b> Could not process your request.</div>`;
  }

  setTimeout(() => {
    const box = document.querySelector('.overflow-y-auto');
    box?.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
  }, 100);
};
</script>