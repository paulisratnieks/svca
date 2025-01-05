import {expect, describe, it, vi } from 'vitest';
import {config, mount, VueWrapper} from '@vue/test-utils';
import VideoWindowLayout from '@/components/VideoWindowLayout.vue';
import {defaultOptions} from 'primevue';
import {createTestingPinia} from '@pinia/testing';

config.global.mocks['$primevue'] = {
	config: defaultOptions
};

const ResizeObserverMock = vi.fn(() => ({
	observe: vi.fn(),
	unobserve: vi.fn(),
}));

vi.stubGlobal('ResizeObserver', ResizeObserverMock);

const MockTrack = vi.fn(function (type) {
	this.type = type
});
MockTrack.prototype.attach = vi.fn();

function render(props: Record<string, unknown>): VueWrapper<VideoWindowLayout> {
	const wrapper = mount(VideoWindowLayout,
		{
			props: {
				...props,
				'onUpdate:modelValue': (e) => wrapper.setProps({modelValue: e}),
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: {'auth': {id: 1, name: 'user'}}
				})]
			}
		}
	);

	return wrapper;
}

describe('VideoWindowLayout', () => {
	it('video window renders grid layout when no participant is screen sharing', () => {
		const wrapper = render({
			participants: [{user: {id: 1, name: 'name'}}]
		});

		expect(wrapper.find('.videos').classes()).contains('grid');
	});

	it('video window renders carousel layout when any participant is screen sharing', async () => {
		const mockScreenVideoTrack = new MockTrack('screenVideo');
		const mockScreenAudioTrack = new MockTrack('screenAudio');
		const mockAudioTrack = new MockTrack('audio');
		const mockVideoTrack = new MockTrack('video');
		const participantWithoutTracks = {user: {id: 1, name: 'name'}};
		const wrapper = render({
					participants: [participantWithoutTracks]
		});
		await wrapper.setProps({
			participants: [
				{
					...participantWithoutTracks,
					screenVideoTrack: mockScreenVideoTrack,
					screenAudioTrack: mockScreenAudioTrack,
					audioTrack: mockAudioTrack,
					videoTrack: mockVideoTrack,
				},
			]
		});

		expect(wrapper.find('.videos').classes()).contains('carousel');
		const allVideoWindowComponents = wrapper.findAllComponents({name: 'VideoWindow'})
		expect(allVideoWindowComponents[0].props('videoTrack').type).toEqual('screenVideo');
		expect(allVideoWindowComponents[0].props('audioTrack').type).toEqual('screenAudio');
		expect(allVideoWindowComponents[1].props('videoTrack').type).toEqual('video');
		expect(allVideoWindowComponents[1].props('audioTrack').type).toEqual('audio');
	});

	it('component renders VideoWindow and Paginator', () => {
		const maxVideoWindowCountGridLayout = 9;
		const participants= Array.from({length: maxVideoWindowCountGridLayout + 1}, (_, i) => {
			return {user: {id: i, name: 'user'}};
		});

		const wrapper = render({
			participants: participants
		});
		expect(wrapper.findAllComponents({name: 'VideoWindow'}).length).toEqual(maxVideoWindowCountGridLayout);
		expect(wrapper.findComponent({name: 'Paginator'}).exists).toBeTruthy();
	});
});
