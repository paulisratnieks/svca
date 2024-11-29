import {expect, describe, it} from 'vitest';
import {mount} from '@vue/test-utils';
import ChatMessage from '@/components/ChatMessage.vue';

describe('ChatMessage', () => {
	const time = '10:11';
	const defaultProps = {
		username: 'username',
		message: 'message',
		timestamp: Date.parse(`2019-01-01T${time}:12`),
		isAuthenticatedPersonAuthor: true,
		showAuthor: true,
		showDate: true,
	}

	it('can render with default props', () => {
		const wrapper = mount(ChatMessage, {props: {...defaultProps}});

		expect(wrapper.find('.body').text()).toEqual(defaultProps.message);
		expect(wrapper.find('.author').text()).toEqual(defaultProps.username);
		expect(wrapper.find('.date').text()).toEqual(time);
		expect(wrapper.find('.my-message').exists()).toBeTruthy();
	});

	it('test boolean props arguments', () => {
		const wrapper = mount(ChatMessage,
			{
				props: {
					...defaultProps,
					showDate: false,
					showAuthor: false,
					isAuthenticatedPersonAuthor: false,
				}
			});

		expect(wrapper.find('.body').text()).toEqual(defaultProps.message);
		expect(wrapper.find('.author').exists()).toBeFalsy()
		expect(wrapper.find('.date').exists()).toBeFalsy();
		expect(wrapper.find('.my-message').exists()).toBeFalsy();
	});
});
