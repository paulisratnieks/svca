import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import MicrophoneIcon from '@/components/icons/MicrophoneIcon.vue';

describe('MicrophoneIcon', () => {
	it('off state selects off icon', () => {
		const wrapper = mount(MicrophoneIcon,
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
		const wrapper = mount(MicrophoneIcon,
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
