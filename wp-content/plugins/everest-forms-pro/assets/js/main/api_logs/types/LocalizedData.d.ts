export {};

interface ScriptData {
	adminURL: string;
	siteURL: string;
	apiLogRestNonce: string;
	restURL: string;
	builderURL: string;
	viewEntryURL: string;
}

declare global {
	interface Window {
		evfp_api_logs_script: ScriptData;
	}
}
