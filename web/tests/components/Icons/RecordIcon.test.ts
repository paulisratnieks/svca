import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import RecordIcon from '@/components/icons/RecordIcon.vue';

describe('RecordIcon', () => {
	it('off state selects off icon', () => {
		const wrapper = mount(RecordIcon,
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
		const wrapper = mount(RecordIcon,
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
