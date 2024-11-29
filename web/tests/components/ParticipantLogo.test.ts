import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import ParticipantLogo from '@/components/ParticipantLogo.vue';

describe('ParticipantLogo', () => {
	it('component mounts with regular name', () => {
		const wrapper = mount(ParticipantLogo,
			{
				props: {
					name: 'test user'
				},
			}
		);
		expect(wrapper.find('div:not(.person-initials)').text()).toEqual('TU');
	});

	it('component mounts with one word name', () => {
		const wrapper = mount(ParticipantLogo,
			{
				props: {
					name: 'test'
				},
			}
		);
		expect(wrapper.find('div:not(.person-initials)').text()).toEqual('T');
	});
});
