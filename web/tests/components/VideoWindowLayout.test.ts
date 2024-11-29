import {expect, describe, it, vi } from 'vitest';
import {config, mount} from '@vue/test-utils';
import VideoWindowLayout from '@/components/VideoWindowLayout.vue';
import {defaultOptions} from 'primevue';

config.global.mocks['$primevue'] = {
	config: defaultOptions
};

const ResizeObserverMock = vi.fn(() => ({
	observe: vi.fn(),
	unobserve: vi.fn(),
}));

vi.stubGlobal('ResizeObserver', ResizeObserverMock);

const MockTrack = vi.fn(function () {});
MockTrack.prototype.attach = vi.fn();

describe('VideoWindowLayout', () => {
	it('video window renders grid layout when no participant is screen sharing', () => {
		const wrapper = mount(VideoWindowLayout,
			{
				props: {
					participants: [{user: {id: 1, name: 'name'}}]
				},
			}
		);

		expect(wrapper.find('.videos').classes()).contains('grid');
	});

	it('video window renders carousel layout when any participant is screen sharing', async () => {
		const mockScreenVideoTrack = new MockTrack();
		const participantWithoutTracks = {user: {id: 1, name: 'name'}};
		const wrapper = mount(VideoWindowLayout,
			{
				props: {
					participants: [participantWithoutTracks]
				},
			}
		);
		await wrapper.setProps({
			participants: [
				{
					...participantWithoutTracks,
					screenVideoTrack: mockScreenVideoTrack,
				},
			]
		});

		expect(wrapper.find('.videos').classes()).contains('carousel');
	});

	it('component renders VideoWindow and Paginator', () => {
		const maxVideoWindowCountGridLayout = 9;
		const participants= Array.from({length: maxVideoWindowCountGridLayout + 1}, (_, i) => {
			return {user: {id: i, name: 'user'}};
		});

		const wrapper = mount(VideoWindowLayout,
			{
				props: {
					participants: participants
				},
			}
		);
		expect(wrapper.findAllComponents({name: 'VideoWindow'}).length).toEqual(maxVideoWindowCountGridLayout);
		expect(wrapper.findComponent({name: 'Paginator'}).exists).toBeTruthy();
	});
});
