import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import UserIcon from '@/components/icons/UserIcon.vue';

describe('UserIcon', () => {
	it('mounts', () => {
		const wrapper = mount(UserIcon);
		expect(wrapper.find('img')).toBeTruthy();
	});
});
