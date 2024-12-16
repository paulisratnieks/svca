import {expect, describe, it, vi} from 'vitest';
import {config, mount, VueWrapper} from '@vue/test-utils';
import MeetingSidebar from '@/components/MeetingSidebar.vue';
import {createTestingPinia} from '@pinia/testing';
import {TabNames} from '@/types/tab-names';
import {defaultOptions} from 'primevue';

config.global.mocks['$primevue'] = {
	config: defaultOptions
};

const authenticatedUser = {id: 1, name: 'user'}
const defaultProps = {
	messages: [{timestamp: Date.now(), message: 'message', userId: 1}],
	participants: [{user: authenticatedUser}],
	modelValue: TabNames.Chat,
}

function render(props: Record<string, unknown>): VueWrapper<MeetingSidebar> {
	const wrapper = mount(MeetingSidebar,
		{
			props: {
				...props,
				'onUpdate:modelValue': (e) => wrapper.setProps({modelValue: e}),
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: {'auth': {user: authenticatedUser}}
				})]
			}
		}
	);

	return wrapper;
}

describe('MeetingSidebar', () => {
	it('can switch tabs correctly', async () => {
		const wrapper = render(defaultProps);
		expect(wrapper.find('.tab-panel').exists()).toBeTruthy();
		await wrapper.find('.participants').trigger('click');
		expect(wrapper.props('modelValue')).toEqual(TabNames.Participants);
		await wrapper.find('.chat').trigger('click');
		expect(wrapper.props('modelValue')).toEqual(TabNames.Chat);
	});

	it('emits close even when close is clicked', async () => {
		const wrapper = render(defaultProps);
		await wrapper.find('.close').trigger('click');
		expect(wrapper.emitted('close')).toBeTruthy();
	});

	it('chat list renders chat message component', async () => {
		const wrapper = render(defaultProps);
		expect(wrapper.findComponent({name: 'ChatMessage'}).props()).toEqual({
			username: authenticatedUser.name,
			message: defaultProps.messages[0].message,
			timestamp: defaultProps.messages[0].timestamp,
			isAuthenticatedPersonAuthor: true,
			showAuthor: true,
			showDate: true,
		});
	});

	it('can create new chat message', async () => {
		const wrapper = render(defaultProps);
		const messageInput = wrapper.findComponent({name: 'InputText'});
		await messageInput.setValue('new message');
		await messageInput.trigger('keydown.enter');
		expect(wrapper.emitted('messageCreated')[0][0]).toEqual('new message');
	});

	it('participants list renders participants', async () => {
		const wrapper = render({...defaultProps, modelValue: TabNames.Participants});
		expect(wrapper.find('.participant-block span:not(.media)').text()).toEqual(authenticatedUser.name);
		expect(wrapper.findComponent({name: 'MicrophoneIcon'}).props('isOff')).toEqual(false);
		expect(wrapper.findComponent({name: 'CameraIcon'}).props('isOff')).toEqual(false);
		expect(wrapper.findComponent({name: 'CameraIcon'}).props('isOff')).toEqual(false);
	});

	it('can search participants', async () => {
		const wrapper = render({...defaultProps, modelValue: TabNames.Participants});
		const searchInput = wrapper.findComponent({name: 'InputText'});
		await searchInput.setValue('user');
		expect(wrapper.find('.participant-block span:not(.media)').text()).toEqual(authenticatedUser.name);
		await searchInput.setValue('users');
		expect(wrapper.find('.participant-block span:not(.media)').exists()).toBeFalsy();
	});
});
