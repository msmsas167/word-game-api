<template>
    <div v-if="show" @click.self="$emit('close')" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl shadow-xl p-8 text-center max-w-sm w-full">
        <h2 class="title-font text-5xl text-teal-600 mb-4">{{ title }}</h2>
        <slot></slot>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    show: Boolean,
    title: String
  });
  defineEmits(['close']);
  </script>
  
  <style scoped>
  .title-font {
    font-family: 'Pacifico', cursive;
  }
  </style>