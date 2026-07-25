import { NonEmptyArray } from './analytics';

export type PreferencesApiResponse = {
	summary: string[];
	visualization: string[];
	updated_at: string;
};

type BaseChart = {
	metric: string;
	date_from: string;
	date_to: string;
	intervals: Array<string>;
	total: number;
	previous_total: number;
	avg: number;
	avg_delta: number;
	delta: number;
	is_money?: boolean;
	currency?: string;
	rate?: number;
	[k: string]: unknown;
};

type TimeSeriesData = Array<{
	time: string;
	value: number;
}>;

type CategoryData = Array<{
	label: string;
	value: number;
}>;

type ChartData =
	| {
			slug: string;
			data: TimeSeriesData;
			previous_data: TimeSeriesData;
	  }
	| {
			slug: string;
			data: CategoryData;
			previous_data: TimeSeriesData;
	  };

export type Chart = BaseChart & ChartData;

export interface OverviewApiResponse {
	url: string;
	version: string;
	props: {
		overview: {
			charts: Array<Chart>;
			approvedUsers: number;
			approvedUsersPercentage: number;
			newRegistration: number;
			newRegistrationPercentage: number;
			pendingUsers: number;
			pendingUsersPercentage: number;
			deniedUsers: number;
			deniedUsersPercentage: number;
			unit: 'month' | 'day' | 'week';
			totalRegistration: number;
			totalRegistrationPercentage: number;
			dateFrom: string;
			dateTo: string;
			dateDifference: string;
			comparisonPercentage: number;
			totalFormRegistration: number;
			forms: Array<{
				id: number;
				name: string;
			}>;
			validUnits: ['day', 'week', 'month'];
			memberships: Array<{
				id: number;
				name: string;
			}>;
		};
	};
}

export type DataSets = {
	summary: NonEmptyArray<{
		slug: string;
		label: string;
	}>;
	visualization: NonEmptyArray<{
		slug: string;
		label: string;
		metrics: NonEmptyArray<{
			slug: string;
			label: string;
		}>;
		type?: 'pie' | 'list';
	}>;
};
