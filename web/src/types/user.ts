export interface User {
	// Api fields
	id: number,
	name: string,
	super_user?: boolean,

	// Frontend dynamic fields
	isSpeaking?: boolean,
}
