import type {LocalTrack, Track} from 'livekit-client';
import type {User} from '@/types/user';

type TrackTypes = LocalTrack | Track;

export interface UserWithTracksBase<T extends TrackTypes = TrackTypes> {
	user: User
	audioTrackMuted?: boolean,
	videoTrackMuted?: boolean,
	audioTrack?: T,
	videoTrack?: T,
	screenVideoTrack?: T,
	screenAudioTrack?: T,
}
