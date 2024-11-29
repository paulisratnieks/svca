import {expect, describe, it } from 'vitest';
import {mount} from '@vue/test-utils';
import ParticipantLabel from '@/components/ParticipantLabel.vue';

describe('ParticipantLabel', () => {
	it('component renders props correctly', () => {
		const props = {
			name: 'name',
			isTrackMuted: false,
		}
		const wrapper = mount(ParticipantLabel,
			{
				props: {...props},
			}
		);
		expect(wrapper.find('span').text()).toEqual(props.name);
		expect(wrapper.findComponent({name: 'MicrophoneIcon'}).props('isOff')).toEqual(props.isTrackMuted);
	});
});
