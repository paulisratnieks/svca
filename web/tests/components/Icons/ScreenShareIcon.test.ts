import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import ScreenShareIcon from '@/components/icons/ScreenShareIcon.vue';

describe('ScreenShareIcon', () => {
	it('off state selects off icon', () => {
		const wrapper = mount(ScreenShareIcon,
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
		const wrapper = mount(ScreenShareIcon,
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
