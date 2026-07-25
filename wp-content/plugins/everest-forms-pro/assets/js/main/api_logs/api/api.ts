import { apiLogScriptData } from "./../utils/global";
import apiFetch from "@wordpress/api-fetch";
import { GetContentProps } from "../../../../../../easy-mail-smtp/assets/js/main/types/index";

const { restURL, apiLogRestNonce } = apiLogScriptData;

const BASEURL = "evfp/v1/";

const ENDPOINTS = {
	GetLogs: `${BASEURL}api-logs/get`,
	GetLogsContent: `${BASEURL}api-logs/get/content`,
	DeleteLogs: `${BASEURL}api-logs/delete/logs`,
};

export const getApiLogs = async ({ queryKey }: any) => {
	const [, params] = queryKey;
	return apiFetch({
		path: ENDPOINTS.GetLogs,
		method: "POST",
		headers: {
			"X-WP-Nonce": apiLogRestNonce,
		},
		data: params,
	});
};
export const getLogContent = async (props: GetContentProps) => {
	return apiFetch({
		path: ENDPOINTS.GetLogsContent,
		method: "POST",
		headers: {
			"X-WP-Nonce": apiLogRestNonce,
		},
		data: {
			id: props.id,
		},
	}).then((res) => res);
};

export const deleteLogs = async (props: any) => {
	return apiFetch({
		path: ENDPOINTS.DeleteLogs,
		method: "POST",
		headers: {
			"X-WP-Nonce": apiLogRestNonce,
		},
		data: {
			ids: props,
		},
	}).then((res) => res);
};
