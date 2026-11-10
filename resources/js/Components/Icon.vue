<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 18 });
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'image'">
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="m21 15-4.5-4.5L7 21" />
        </template>

        <template v-else-if="name === 'video'">
            <rect x="2" y="5" width="14" height="14" rx="3" />
            <path d="m16 10 5-3v10l-5-3z" />
        </template>

        <template v-else-if="name === 'audio'">
            <path d="M3 12h2m3-5v10m4-13v16m4-12v8m4-5v2" />
        </template>

        <template v-else-if="name === 'text'">
            <path d="M4 6h16M4 12h12M4 18h8" />
        </template>

        <template v-else-if="name === 'spark'">
            <path d="M12 3v4m0 10v4M3 12h4m10 0h4M5.6 5.6l2.8 2.8m7.2 7.2 2.8 2.8m0-12.8-2.8 2.8m-7.2 7.2-2.8 2.8" />
        </template>

        <template v-else-if="name === 'plus'">
            <path d="M12 5v14M5 12h14" />
        </template>

        <template v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </template>

        <template v-else-if="name === 'close'">
            <path d="M6 6l12 12M18 6 6 18" />
        </template>

        <template v-else-if="name === 'check'">
            <path d="m5 13 4 4L19 7" />
        </template>

        <template v-else-if="name === 'alert'">
            <path d="M12 8v5m0 3v.5" />
            <circle cx="12" cy="12" r="9" />
        </template>

        <template v-else-if="name === 'download'">
            <path d="M12 4v11m0 0 4-4m-4 4-4-4M4 19h16" />
        </template>

        <template v-else-if="name === 'trash'">
            <path d="M4 7h16M9 7V5h6v2m-8 0 1 13h8l1-13" />
        </template>

        <template v-else-if="name === 'grid'">
            <rect x="3" y="3" width="7" height="7" rx="2" />
            <rect x="14" y="3" width="7" height="7" rx="2" />
            <rect x="3" y="14" width="7" height="7" rx="2" />
            <rect x="14" y="14" width="7" height="7" rx="2" />
        </template>

        <template v-else-if="name === 'layers'">
            <path d="m12 3 9 5-9 5-9-5 9-5Z" />
            <path d="m3 13 9 5 9-5" />
        </template>

        <template v-else-if="name === 'chart'">
            <path d="M4 20V10m5 10V4m5 16v-7m5 7V8" />
        </template>

        <template v-else-if="name === 'settings'">
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6l1.4 1.4m10 10 1.4 1.4m0-12.8-1.4 1.4m-10 10-1.4 1.4" />
        </template>

        <template v-else-if="name === 'arrow-right'">
            <path d="M5 12h14m0 0-6-6m6 6-6 6" />
        </template>

        <template v-else-if="name === 'logout'">
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 12H4m0 0 3-3m-3 3 3 3" />
        </template>

        <template v-else-if="name === 'refresh'">
            <path d="M20 12a8 8 0 1 1-2.3-5.6M20 4v4h-4" />
        </template>

        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </template>

        <template v-else-if="name === 'bolt'">
            <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" />
        </template>

        <template v-else-if="name === 'shield'">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z" />
            <path d="m9 12 2 2 4-4" />
        </template>

        <template v-else-if="name === 'code'">
            <path d="m8 8-4 4 4 4m8-8 4 4-4 4M14 5l-4 14" />
        </template>

        <template v-else>
            <circle cx="12" cy="12" r="9" />
        </template>
    </svg>
</template>
