<script setup lang="ts">
import type {User} from '@/types/user';
import type {Track} from 'livekit-client';
import VideoWindow from '@/components/VideoWindow.vue';
import {computed, type ComputedRef, onMounted, onUnmounted, ref, type Ref, useTemplateRef, watch} from 'vue';
import Paginator from 'primevue/paginator';

const props = defineProps<{
	participants: {audioTrack?: Track, videoTrack?: Track, user: User}[],
}>();

const maxVideoCountPerPage: number = 9;

const firstRecordCountInPage: Ref<number> = ref(0);
const resizeObserver: Ref<ResizeObserver|null> = ref(null);
const isSizeWidth: Ref<boolean> = ref(true);
const sizeInPixels: Ref<number> = ref(0);
const videosContainer: Ref<HTMLDivElement|null> = useTemplateRef('videos');

const videosCount: Ref<number> = computed(() => {
	return props.participants.length + 1;
});

const isPaginatorVisible: Ref<boolean> = computed(() => {
	return videosCount.value > maxVideoCountPerPage;
});

const visibleVideos: ComputedRef<{audioTrack?: Track, videoTrack?: Track, user: User}[]> = computed(() => {
	return props.participants.slice(firstRecordCountInPage.value, maxVideoCountPerPage + firstRecordCountInPage.value);
});

function onResize(): void {
	updateVideoLayout();
}

function updateVideoLayout(): void {
	const videosCount = visibleVideos.value.length;
	const columnCount = Math.ceil(Math.sqrt(videosCount));
	const rowCount = Math.ceil(videosCount / columnCount);

	const containerWidth = videosContainer.value?.offsetWidth;
	const containerHeight = videosContainer.value?.offsetHeight;
	if (!containerWidth || !containerHeight) return;

	const gap = 8;
	const inverseAspectRatio = 9 / 16;

	const width = containerWidth * (1 / columnCount) - (gap * (columnCount - 1));
	const height = containerHeight * (1 / rowCount) - (gap * (rowCount - 1));

	const heightUsingCalculatedWidth = width * inverseAspectRatio;

	const fullContainerHeight = heightUsingCalculatedWidth * rowCount + (gap * (rowCount - 1));
	if (fullContainerHeight > containerHeight) {
		isSizeWidth.value = false;
		sizeInPixels.value = height;
	} else {
		isSizeWidth.value = true;
		sizeInPixels.value = width;
	}
}

watch(
	visibleVideos,
	() => {
		updateVideoLayout()
	}
)

onMounted(() => {
	resizeObserver.value = new ResizeObserver(() => onResize());
	if (videosContainer.value) {
		resizeObserver.value.observe(videosContainer.value);
	}
	updateVideoLayout();
});

onUnmounted(() => {
	if (videosContainer.value) {
		resizeObserver.value?.unobserve(videosContainer.value);
	}
});

</script>

<template>
	<section class="videos-container">
		<div class="videos" ref="videos">
			<VideoWindow
				v-for="(participant, index) in visibleVideos"
				:size="sizeInPixels"
				:is-size-width="isSizeWidth"
				:key="index"
				:audio-track="participant.audioTrack"
				:video-track="participant.videoTrack"
				:user="participant.user"
			/>
		</div>
		<div class="paginator-container">
			<Paginator
				v-if="isPaginatorVisible"
				class="paginator"
				v-model:first="firstRecordCountInPage"
				:rows="9"
				:totalRecords="10"
				template="PrevPageLink CurrentPageReport NextPageLink"
				currentPageReportTemplate="{currentPage} of {totalPages}"
			/>
		</div>
	</section>
</template>

<style lang="scss" scoped>
section.videos-container {
	width: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;

	div.videos {
		display: flex;
		gap: 8px;
		align-content: center;
		flex-wrap: wrap;
		align-items: center;
		justify-content: center;
		flex: 1;
	}

	.paginator-container {
		width: 100%;
		display: flex;
		justify-content: center;
		margin-bottom: 8px;

		:deep(.p-paginator) {
			padding: 0;
		}
	}
}
</style>
