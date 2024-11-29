import {expect, describe, it, vi } from 'vitest';
import {mount} from '@vue/test-utils';
import VideoWindow from '@/components/VideoWindow.vue';

const MockTrack = vi.fn(function () {});
MockTrack.prototype.attach = vi.fn();

describe('VideoWindow', () => {
	it('active speaker user has additional class', () => {
		const wrapper = mount(VideoWindow,
			{
				props: {
					user: {id: 1, name: 'name', isSpeaking: true},
				},
			}
		);
		expect(wrapper.find('.video-window').classes()).toContain('active-speaker');
	});

	it('attach gets called when new tracks are available', () => {
		const mockVideoTrack = new MockTrack();
		const mockAudioTrack = new MockTrack();
		mount(VideoWindow,
			{
				props: {
					user: {id: 1, name: 'name'},
					audioTrack: mockAudioTrack,
					videoTrack: mockVideoTrack,
				},
			}
		);
		expect(mockVideoTrack.attach).toBeCalled();
		expect(mockAudioTrack.attach).toBeCalled();
	});
});
