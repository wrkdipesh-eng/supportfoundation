import { __ } from '@wordpress/i18n';
import { DateRangeToUnits, DateUnit } from '../types/analytics';

export const getDaysDiff = (date1: Date, date2: Date) => {
	const diffTime = Math.abs(date2.getTime() - date1.getTime());
	return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

export const getUnitsForDayRange = (days: number): Array<DateUnit> => {
	if (days <= 1) return ['hour'];
	if (days <= 7) return ['day', 'hour'];
	if (days <= 14) return ['day', 'week'];
	if (days <= 30) return ['day', 'week'];
	if (days <= 90) return ['week', 'month'];
	if (days <= 365) return ['month'];
	return ['year'];
};

const getAllTimeUnits = (): Array<DateUnit> => {
	const installDate = new Date(window.__EVF_ANALYTICS__.install_date);
	const currentDate = new Date();
	const daysDiff = getDaysDiff(installDate, currentDate);
	return getUnitsForDayRange(daysDiff);
};

export const DATE_RANGE_TO_UNITS: DateRangeToUnits = {
	today: ['hour'],
	last7: ['day', 'hour'],
	last14: ['day', 'week'],
	last30: ['day', 'week'],
	last3Months: ['week', 'month'],
	last12Months: ['month'],
	monthToDate: ['day', 'week'],
	quarterToDate: ['day', 'week', 'month'],
	yearToDate: ['month', 'week'],
	get allTime() {
		return getAllTimeUnits();
	},
};

export const DATE_UNITS: Array<{
	label: string;
	value: DateUnit;
}> = [
	{
		label: __('Hour'),
		value: 'hour',
	},
	{
		label: __('Day'),
		value: 'day',
	},
	{
		label: __('Week'),
		value: 'week',
	},
	{
		label: __('Month'),
		value: 'month',
	},
	{
		label: __('Year'),
		value: 'year',
	},
];

export const CHART_COLORS = [
	'#3B82F6',
	'#10B981',
	'#F59E0B',
	'#EF4444',
	'#8B5CF6',
	'#06B6D4',
	'#EC4899',
	'#14B8A6',
	'#F97316',
	'#6366F1',
	'#84CC16',
	'#A855F7',
	'#22D3EE',
	'#F43F5E',
	'#0EA5E9',
	'#34D399',
	'#FB923C',
	'#A78BFA',
	'#60A5FA',
	'#FBBF24',
];
