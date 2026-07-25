import apiFetch from "@wordpress/api-fetch";
const { restURL, security } =
	typeof evf_payment_log !== "undefined" && evf_payment_log;

const base = "everest-forms-pro/v1/";

const urls = {
	getFormList: base + "payment_log/get-form-list",
	getActiveGateways: base + "payment_log/get-active-gateways",
	getPaymentEntryData: base + "payment_log/get-payment-entry",
	deletePaymentEntries: base + "payment_log/delete-entries",
};
export const getFormList = async () => {
	return apiFetch({
		path: urls.getFormList,
		method: "GET",
		headers: {
			"X-WP-Nonce": security,
		},
	}).then((res) => res);
};

export const getActiveGateways = async () => {
	return apiFetch({
		path: urls.getActiveGateways,
		method: "GET",
		headers: {
			"X-WP-Nonce": security,
		},
	}).then((res) => res);
};

export const deletePaymentEntries = async (ids) => {
	return apiFetch({
		path: urls.deletePaymentEntries,
		method: "DELETE",
		headers: {
			"X-WP-Nonce": security,
		},
		data: { ids },
	});
};

export const getPaymentEntryData = async (
	pageSize,
	offset,
	formId,
	paymentStatus,
	searchQuery = "",
	gateway = "",
	orderBy = "date_created",
	order = "desc",
) => {
	return apiFetch({
		path: urls.getPaymentEntryData,
		method: "POST",
		headers: {
			"X-WP-Nonce": security,
		},
		data: {
			request: {
				page_size: pageSize,
				offset: offset,
				form_id: formId,
				payment_status: paymentStatus,
				search_query: searchQuery,
				gateway: gateway,
				order_by: orderBy,
				order: order,
			},
		},
	});
};
