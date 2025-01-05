import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import CameraIcon from '@/components/icons/CameraIcon.vue';

describe('CameraIcon', () => {
	it('off state selects off icon', () => {
		const wrapper = mount(CameraIcon,
			{
				props: {
					isOff: true,
				},
			}
		);
		expect(wrapper.find('img')).toBeTruthy();
		expect(wrapper.find('img').attributes('src')).contains('off')
	});

	it('on state selects on icon', () => {
		const wrapper = mount(CameraIcon,
			{
				props: {
					isOff: false,
				},
			}
		);
		expect(wrapper.find('img')).toBeTruthy();
		expect(wrapper.find('img').attributes('src')).not.contains('off');
	});
});
