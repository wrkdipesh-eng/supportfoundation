export type NonEmptyArray<T> = [T, ...T[]];

type TimeSeriesData = Array<{
	time: string;
	value: number;
}>;

type CategoryData = Array<{
	label: string;
	value: number;
}>;

export type DateUnit = "hour" | "day" | "week" | "month" | "year";
export type DateRange =
	| "today"
	| "last7"
	| "last14"
	| "last30"
	| "last3Months"
	| "last12Months"
	| "monthToDate"
	| "quarterToDate"
	| "yearToDate"
	| "allTime";
export type DateRangeToUnits = Record<DateRange, Array<DateUnit>>;

interface Metric {
	count: number;
	previous: number;
	percentage_change: number;
	currency: boolean;
}

export interface TimeSeriesPoint {
	period: string;
	total: number;
	complete: number;
	incomplete: number;
	revenue: number;
	// Overview — requires additional backend tracking
	bounced?: number;
	impressions?: number;
	// Entry status — requires additional backend tracking
	read?: number;
	unread?: number;
	trash?: number;
	spam?: number;
	pending?: number;
	starred?: number;
	// Payment transaction counts per period
	payment_complete?: number;
	payment_pending?: number;
	payment_failed?: number;
	payment_refunded?: number;
}

export interface OverviewApiResponse {
	new_members: Metric;
	approved_members: Metric;
	pending_members: Metric;
	denied_members: Metric;
	total_revenue: Metric;
	average_order_value: Metric;
	refunded_revenue: Metric;
	transactions: Metric;
	refund_count: Metric;
	active_forms: Metric;
	unique_respondents: Metric;
	impressions_metric?: Metric;
	bounced_metric?: Metric;
	time_series: TimeSeriesPoint[];
	forms: Array<{ id: number; title: string; is_payment_form?: boolean; is_survey_form?: boolean }>;
	top_forms?: Array<{ title: string; count: number; revenue: number }>;
	device_breakdown?: { desktop: number; mobile: number; tablet: number };
	top_referral_pages?: Array<{ url: string; title: string; count: number }>;
}
