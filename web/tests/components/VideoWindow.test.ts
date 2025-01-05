import {expect, describe, it, vi } from 'vitest';
import {mount, VueWrapper} from '@vue/test-utils';
import VideoWindow from '@/components/VideoWindow.vue';
import {createTestingPinia} from '@pinia/testing';

const MockTrack = vi.fn(function () {});
MockTrack.prototype.attach = vi.fn();

function render(props: Record<string, unknown>): VueWrapper<VideoWindow> {
	const wrapper = mount(VideoWindow,
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

describe('VideoWindow', () => {
	it('active speaker user has additional class', () => {
		const wrapper = render({
			user: {id: 1, name: 'name', isSpeaking: true},
		});
		expect(wrapper.find('.video-window').classes()).toContain('active-speaker');
	});

	it('attach gets called when new tracks are available', () => {
		const mockVideoTrack = new MockTrack();
		const mockAudioTrack = new MockTrack();
		render({
			user: {id: 1, name: 'name'},
			audioTrack: mockAudioTrack,
			videoTrack: mockVideoTrack,
		});
		expect(mockVideoTrack.attach).toBeCalled();
		expect(mockAudioTrack.attach).toBeCalled();
	});
});
