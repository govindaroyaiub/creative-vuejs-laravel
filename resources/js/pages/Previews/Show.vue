<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps<{ preview: { id: number } }>();
const previewId = props.preview.id;
const categories = ref<any[]>([]);
const catLoading = ref(false);
const feedbacksByCat = ref<Record<number, any[]>>({});
const setsByFeedback = ref<Record<number, any[]>>({});
const versionsBySet = ref<Record<number, any[]>>({});
const bannersByVersion = ref<Record<number, { data: any[], meta: any }>>({});

const activeCategoryId = ref<number|null>(null);
const activeFeedbackId = ref<number|null>(null);

// Loaders
const loadCategories = async () => {
  catLoading.value = true;
  const { data } = await axios.get(`/previews/${previewId}/categories`);
  categories.value = data;
  catLoading.value = false;
};

const onSelectCategory = async (catId: number) => {
  activeCategoryId.value = catId;
  if (!feedbacksByCat.value[catId]) {
    const { data } = await axios.get(`/categories/${catId}/feedbacks`);
    feedbacksByCat.value[catId] = data;
  }
};

const onSelectFeedback = async (feedbackId: number) => {
  activeFeedbackId.value = feedbackId;
  if (!setsByFeedback.value[feedbackId]) {
    const { data } = await axios.get(`/feedbacks/${feedbackId}/sets`);
    setsByFeedback.value[feedbackId] = data;
  }
};

const loadVersions = async (setId: number) => {
  if (!versionsBySet.value[setId]) {
    const { data } = await axios.get(`/feedback-sets/${setId}/versions`);
    versionsBySet.value[setId] = data;
  }
};

const loadBanners = async (versionId: number, page = 1) => {
  const key = `${versionId}:${page}`;
  if (!bannersByVersion.value[key]) {
    const { data } = await axios.get(`/versions/${versionId}/banners`, { params: { page } });
    bannersByVersion.value[key] = { data: data.data, meta: data };
  }
};

// Toggles (optimistic)
const toggleCategoryActive = async (cat: any) => {
  const old = cat.is_active;
  cat.is_active = !old;
  try { await axios.patch(`/categories/${cat.id}/active`); }
  catch { cat.is_active = old; }
};

const toggleFeedbackActive = async (f: any) => {
  const old = f.is_active;
  f.is_active = !old;
  try { await axios.patch(`/feedbacks/${f.id}/active`); }
  catch { f.is_active = old; }
};

loadCategories();
</script>

<template>
  <!-- Categories list -->
  <div v-if="categories.length">
    <div v-for="cat in categories" :key="cat.id">
      <button @click="onSelectCategory(cat.id)">{{ cat.name }}</button>
      <label>
        <input type="checkbox" :checked="cat.is_active" @change="toggleCategoryActive(cat)" />
        Active
      </label>
    </div>
  </div>

  <!-- Feedback tabs for selected category -->
  <div v-if="activeCategoryId && feedbacksByCat[activeCategoryId]">
    <div class="tabs">
      <button v-for="fb in feedbacksByCat[activeCategoryId]" :key="fb.id"
              @click="onSelectFeedback(fb.id)">
        {{ fb.name }}
      </button>
      <template v-for="fb in feedbacksByCat[activeCategoryId]" :key="`toggle-${fb.id}`">
        <label>
          <input type="checkbox" :checked="fb.is_active" @change="toggleFeedbackActive(fb)" />
          Active
        </label>
      </template>
    </div>
  </div>

  <!-- Sets for selected feedback -->
  <div v-if="activeFeedbackId && setsByFeedback[activeFeedbackId]">
    <div v-for="set in setsByFeedback[activeFeedbackId]" :key="set.id" class="set">
      <div class="set-bar" @click="loadVersions(set.id)">
        {{ set.name || 'Untitled Set' }}
      </div>

      <!-- Versions -->
      <div v-if="versionsBySet[set.id]">
        <div v-for="version in versionsBySet[set.id]" :key="version.id" class="version">
          <div class="version-header" @click="loadBanners(version.id)">
            {{ version.name || 'Version' }}
          </div>

          <!-- Banners (paginated) -->
          <div v-if="bannersByVersion[`${version.id}:1`]">
            <div v-for="b in bannersByVersion[`${version.id}:1`].data" :key="b.id" class="banner-row">
              {{ b.name }} — {{ b.file_size }}
            </div>
            <!-- add pager to call loadBanners(version.id, page) -->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>