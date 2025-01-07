<script setup lang="ts">
import type {Track} from 'livekit-client';
import VideoWindow from '@/components/VideoWindow.vue';
import {computed, type ComputedRef, onMounted, onUnmounted, ref, type Ref, useTemplateRef, watch} from 'vue';
import Paginator from 'primevue/paginator';
import type {UserWithTracks} from '@/types/user-with-tracks';

enum VideoLayoutType {
	Grid = 'grid',
	Carousel = 'carousel'
}

const props = defineProps<{
	participants: UserWithTracks[],
}>();

const isScreenBeingShared: Ref<boolean> = ref(false);
const videoLayout: Ref<VideoLayoutType> = ref(VideoLayoutType.Grid);
const firstRecordCountInPage: Ref<number> = ref(0);
const resizeObserver: Ref<ResizeObserver|null> = ref(null);
const isSizeWidth: Ref<boolean> = ref(true);
const sizeInPixels: Ref<number> = ref(0);
const carouselOtherSizeInPixels: Ref<number> = ref(0);
const videosContainer: Ref<HTMLDivElement|null> = useTemplateRef('videos');

const maxVideoCountPerPage: Ref<number> = computed(() => {
	return videoLayout.value === VideoLayoutType.Grid ? 9 : 5;
});

const isPaginatorVisible: Ref<boolean> = computed(() => {
	return props.participants.length > maxVideoCountPerPage.value;
});

const screenVideoTracks: Ref<(Track|undefined)[]> = computed(() => {
	return props.participants.map(participant => participant.screenVideoTrack)
});

const visibleVideos: ComputedRef<UserWithTracks[]> = computed(() => {
	return props.participants.slice(firstRecordCountInPage.value, maxVideoCountPerPage.value + firstRecordCountInPage.value);
});

const screenSharingParticipant: ComputedRef<UserWithTracks|undefined> = computed(() => {
	return props.participants.find(participant => participant.screenVideoTrack !== undefined);
});

function onResize(): void {
	updateVideoLayout();
}

function updateVideoLayout(): void {
	if (videoLayout.value === VideoLayoutType.Grid) {
		updateGridLayout();
	} else {
		updateCarouselLayout();
	}
}

function updateGridLayout(): void {
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

function updateCarouselLayout(): void {
	const containerHeight = videosContainer.value?.offsetHeight;
	if (!containerHeight) return;

	const mainVideoHeightPercent = 0.75;
	const otherVideoHeightPercent = 1 - mainVideoHeightPercent;
	const gap = 8;
	isSizeWidth.value = false;
	sizeInPixels.value = (containerHeight * mainVideoHeightPercent) - gap * mainVideoHeightPercent;
	carouselOtherSizeInPixels.value = (containerHeight * otherVideoHeightPercent) - gap * otherVideoHeightPercent;
}

function handleScreenShareTrackUpdate(): void {
	const isSomeParticipantScreenSharing = props.participants.some(participant => participant.screenVideoTrack);

	if (isSomeParticipantScreenSharing && !isScreenBeingShared.value) {
		isScreenBeingShared.value = true;
		videoLayout.value = VideoLayoutType.Carousel;
		updateVideoLayout();
	} else if (!isSomeParticipantScreenSharing && isScreenBeingShared.value) {
		isScreenBeingShared.value = false;
		videoLayout.value = VideoLayoutType.Grid;
		updateVideoLayout();
	}
}

watch(
	visibleVideos,
	() => {
		updateVideoLayout();
	}
)

watch(
	screenVideoTracks,
	() => {
		handleScreenShareTrackUpdate()
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
		<div class="videos" ref="videos" :class="videoLayout">
			<template v-if="videoLayout === VideoLayoutType.Grid">
				<VideoWindow
					v-for="(participant, index) in visibleVideos"
					:size="sizeInPixels"
					:is-size-width="isSizeWidth"
					:key="index"
					:audio-track="participant.audioTrack"
					:audio-track-muted="participant.audioTrackMuted"
					:video-track="participant.videoTrack"
					:video-track-muted="participant.videoTrackMuted"
					:user="participant.user"
				/>
			</template>
			<template v-if="videoLayout === VideoLayoutType.Carousel">
				<VideoWindow
					v-if="screenSharingParticipant"
					:size="sizeInPixels"
					:is-size-width="isSizeWidth"
					:audio-track="screenSharingParticipant.screenAudioTrack"
					:audio-track-muted="screenSharingParticipant.audioTrackMuted ?? screenSharingParticipant.audioTrack?.isMuted"
					:video-track="screenSharingParticipant.screenVideoTrack"
					:video-track-muted="false"
					:user="screenSharingParticipant.user"
				/>
				<div>
					<VideoWindow
						v-for="(participant, index) in visibleVideos"
						:size="carouselOtherSizeInPixels"
						:is-size-width="isSizeWidth"
						:key="index"
						:audio-track="participant.audioTrack"
						:audio-track-muted="participant.audioTrackMuted"
						:video-track="participant.videoTrack"
						:video-track-muted="participant.videoTrackMuted"
						:user="participant.user"
					/>
				</div>
			</template>
		</div>
		<div class="paginator-container" v-if="isPaginatorVisible">
			<Paginator
				class="paginator"
				v-model:first="firstRecordCountInPage"
				:rows="maxVideoCountPerPage"
				:totalRecords="participants.length"
				template="PrevPageLink CurrentPageReport NextPageLink"
				currentPageReportTemplate="{currentPage} of {totalPages}"
			/>
		</div>
	</section>
</template>

<style lang="scss" scoped>
section.videos-container {
	margin: 8px;
	box-sizing: border-box;
	width: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;

	div.videos {
		display: flex;
		height: 100%;
		align-items: center;

		&.grid {
			gap: 8px;
			align-content: center;
			flex-wrap: wrap;
			align-items: center;
			justify-content: center;
			flex: 1;
		}

		&.carousel {
			flex-direction: column;
			gap: 8px;

			> div {
				display: flex;
				gap: 8px;
			}
		}
	}

	.paginator-container {
		width: 100%;
		display: flex;
		justify-content: center;
		margin-top: 8px;

		:deep(.p-paginator) {
			padding: 0;
		}
	}
}
</style>
