import {type Ref, ref} from 'vue';

export function useRTCConnection() {
	const peerConnection: Ref<RTCPeerConnection|null> = ref(null);

	function createPeerConnection(configuration: RTCConfiguration = {}) {
		peerConnection.value = new RTCPeerConnection(configuration);
	}

	function addTracks(stream: MediaStream): void {
		stream.getTracks().forEach((track: MediaStreamTrack): void => {
			peerConnection.value?.addTrack(track, stream);
		});
	}

	function addConnectionEventListeners(
		events: {
			onTrack: (event: RTCTrackEvent) => void
			onIceCandidate: (event: RTCPeerConnectionIceEvent) => void
		}
	): void {
		if (peerConnection.value) {
			peerConnection.value.addEventListener('track', events.onTrack)
			peerConnection.value.addEventListener('icecandidate', events.onIceCandidate)
		}
	}

	function createOffer(
		offerOptions: RTCOfferOptions = {
			offerToReceiveAudio: true,
			offerToReceiveVideo: true
		}
	): Promise<RTCSessionDescriptionInit> {
		let createdOffer: RTCSessionDescriptionInit;

		return peerConnection.value!.createOffer(offerOptions)
			.then((offer: RTCSessionDescriptionInit): Promise<void> => {
				createdOffer = offer;
				return peerConnection.value!.setLocalDescription(offer)
			})
			.then(() => createdOffer);
	}

	function createAnswer(offer: RTCSessionDescriptionInit): Promise<RTCSessionDescriptionInit> {
		let createdAnswer: RTCSessionDescriptionInit;

		return peerConnection.value!.setRemoteDescription(offer)
			.then(() => peerConnection.value!.createAnswer())
			.then((answer: RTCSessionDescriptionInit) => {
				createdAnswer = answer;
				return peerConnection.value!.setLocalDescription(answer)
			})
			.then(() => createdAnswer);
	}

	function setRemoteDescription(answer: RTCSessionDescriptionInit): void {
		if (!peerConnection.value!.currentRemoteDescription) {
			peerConnection.value!.setRemoteDescription(answer);
		}
	}

	function addIceCandidate(candidate: RTCIceCandidate): void {
		peerConnection.value?.addIceCandidate(candidate);
	}

	return {
		createPeerConnection,
		addConnectionEventListeners,
		addTracks,
		createOffer,
		createAnswer,
		addIceCandidate,
		setRemoteDescription
	}
}