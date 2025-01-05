import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import ChatIcon from '@/components/icons/ChatIcon.vue';

describe('ChatIcon', () => {
	it('mounts', () => {
		const wrapper = mount(ChatIcon);
		expect(wrapper.find('img')).toBeTruthy();
	});
});
