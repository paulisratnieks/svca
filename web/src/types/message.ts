import type {ChatMessage} from 'livekit-client';

export type Message = Omit<ChatMessage, 'id'> & {
	userId: number,
}