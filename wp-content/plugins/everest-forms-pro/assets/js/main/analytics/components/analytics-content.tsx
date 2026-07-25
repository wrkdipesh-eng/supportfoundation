import { __ } from '@wordpress/i18n';
import {
	ArcElement,
	BarElement,
	CategoryScale,
	Chart as ChartJS,
	Tooltip as ChartJSTooltip,
	Filler,
	Legend,
	LinearScale,
	LineElement,
	Plugin,
	PointElement,
	Title,
} from 'chart.js';
import { useEffect, useState } from 'react';
import { Bar, Line, Pie } from 'react-chartjs-2';
import { useAnalyticsFilters } from '../hooks/use-analytics-filters';
import { cn } from '../lib/utils';
import { OverviewApiResponse, TimeSeriesPoint } from '../types/analytics';
import { ExternalLink } from './icons/external-link';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectLabel,
	SelectTrigger,
} from './ui/select';

const createLegendItem = (item: any, chart: any) => {
	const li = document.createElement('li');
	li.className = 'EVF-Analytics-Chart__LegendItem';
	if (item.hidden) li.classList.add('EVF-Analytics-Chart__LegendItem--hidden');

	const box = document.createElement('span');
	box.className = 'EVF-Analytics-Chart__LegendColor';
	box.style.background = item.strokeStyle as string;
	box.style.borderColor = item.strokeStyle as string;
	box.style.borderWidth = item.lineWidth + 'px';

	const text = document.createElement('span');
	text.className = 'EVF-Analytics-Chart__LegendText';
	text.textContent = item.text;

	li.appendChild(box);
	li.appendChild(text);
	li.onclick = (e) => {
		e.stopPropagation();
		if (typeof item.index !== 'undefined') {
			chart.toggleDataVisibility(item.index);
		} else if (typeof item.datasetIndex !== 'undefined') {
			chart.setDatasetVisibility(
				item.datasetIndex,
				!chart.isDatasetVisible(item.datasetIndex),
			);
		}
		chart.update();
	};
	return li;
};

const HTMLLegend: Plugin = {
	id: 'HTMLLegend',
	afterUpdate(chart, _args, options) {
		const container = document.getElementById(options.containerID);
		if (!container) return;
		while (container.firstChild) container.firstChild.remove();
		const items =
			chart.options.plugins?.legend?.labels?.generateLabels?.(chart);
		if (!items || items.length <= 1) return;
		const ul = document.createElement('ul');
		ul.className = 'EVF-Analytics-Chart__LegendList';
		items.forEach((item) => ul.appendChild(createLegendItem(item, chart)));
		container.appendChild(ul);
	},
};

ChartJS.register(
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	BarElement,
	ArcElement,
	Title,
	ChartJSTooltip,
	Legend,
	Filler,
	HTMLLegend,
);

interface AnalyticsContentProps {
	overviewData: OverviewApiResponse;
	boxMetrics: string[];
	selectedFormId?: string;
	onBoxMetricChange: (index: number, value: string) => void;
	primaryChartView: string;
	secondaryViews: string[];
	primaryChartType: 'line' | 'bar';
	onPrimaryChartViewChange: (view: string) => void;
	onSecondaryViewsChange: (views: string[]) => void;
	onPrimaryChartTypeChange: (type: 'line' | 'bar') => void;
}

type PctClass = 'positive' | 'negative' | 'neutral';

interface ResolvedStat {
	value: string;
	pct: string;
	pctClass: PctClass;
	cmp: string;
	rawChange: number;
}

interface StatDef {
	key: string;
	label: string;
	resolve: (data: OverviewApiResponse, currency: string) => ResolvedStat;
}

const formatCurrency = (v: number, currency: string) =>
	new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(v);

const formatPct = (change: number) => {
	if (change === 0) return '0.0%';
	return `${Math.abs(change).toFixed(1)}%`;
};

const pctClass = (change: number, lowerIsBetter = false): PctClass => {
	if (change === 0) return 'neutral';
	const good = lowerIsBetter ? change < 0 : change > 0;
	return good ? 'positive' : 'negative';
};

type ApiMetric = {
	count: number;
	previous: number;
	percentage_change: number;
	currency: boolean;
};

const fromMetric = (
	m: ApiMetric | undefined,

	formatVal: (v: number) => string,
	cmpText: (m: ApiMetric) => string,
	lowerIsBetter = false,
): ResolvedStat => {
	if (!m) return unavailableStat();
	return {
		value: formatVal(m.count),
		pct: formatPct(m.percentage_change),
		pctClass: pctClass(m.percentage_change, lowerIsBetter),
		cmp: cmpText(m),
		rawChange: m.percentage_change,
	};
};

const unavailableStat = (): ResolvedStat => ({
	value: '—',
	pct: 'N/A',
	pctClass: 'neutral',
	cmp: __('data not tracked', 'everest-forms'),
	rawChange: 0,
});

const GENERAL_STATS: StatDef[] = [
	{
		key: 'total',
		label: __('Total Submissions', 'everest-forms'),
		resolve: (data) =>
			fromMetric(
				data.new_members,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
			),
	},
	{
		key: 'complete',
		label: __('Complete Submissions', 'everest-forms'),
		resolve: (data) =>
			fromMetric(
				data.approved_members,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
			),
	},
	{
		key: 'incomplete',
		label: __('Incomplete Submissions', 'everest-forms'),
		resolve: (data) =>
			fromMetric(
				data.pending_members,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
			),
	},
	{
		key: 'impressions',
		label: __('Impressions', 'everest-forms'),
		resolve: (data) => {
			if (!data.impressions_metric) {
				return {
					...unavailableStat(),
					cmp: __(
						'Activate Form Analytics addon to enable tracking',
						'everest-forms',
					),
				};
			}
			return fromMetric(
				data.impressions_metric,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
			);
		},
	},
	{
		key: 'convrate',
		label: __('Conversion Rate', 'everest-forms'),
		resolve: (data) => {
			const complete = data.approved_members?.count ?? 0;
			const total = data.new_members?.count ?? 0;
			const prevComplete = data.approved_members?.previous ?? 0;
			const prevTotal = data.new_members?.previous ?? 0;
			const rate = total > 0 ? (complete / total) * 100 : 0;
			const prevRate = prevTotal > 0 ? (prevComplete / prevTotal) * 100 : 0;
			const change = prevRate > 0 ? ((rate - prevRate) / prevRate) * 100 : 0;
			return {
				value: `${rate.toFixed(1)}%`,
				pct: formatPct(change),
				pctClass: pctClass(change),
				cmp: `${__('vs.', 'everest-forms')} ${prevRate.toFixed(1)}% ${__('last period', 'everest-forms')}`,
				rawChange: change,
			};
		},
	},
	{
		key: 'bounce',
		label: __('Bounce Rate', 'everest-forms'),
		resolve: (data) => {
			if (!data.bounced_metric || !data.impressions_metric) {
				return {
					...unavailableStat(),
					cmp: __(
						'Activate Form Analytics addon to enable tracking',
						'everest-forms',
					),
				};
			}
			const bounced = data.bounced_metric.count;
			const impressions = data.impressions_metric.count;
			const prevBounced = data.bounced_metric.previous;
			const prevImpressions = data.impressions_metric.previous;
			const rate = impressions > 0 ? (bounced / impressions) * 100 : 0;
			const prevRate =
				prevImpressions > 0 ? (prevBounced / prevImpressions) * 100 : 0;
			const change = prevRate > 0 ? ((rate - prevRate) / prevRate) * 100 : 0;
			return {
				value: `${rate.toFixed(1)}%`,
				pct: formatPct(change),
				pctClass: pctClass(change, true),
				cmp: `${__('vs.', 'everest-forms')} ${prevRate.toFixed(1)}% ${__('last period', 'everest-forms')}`,
				rawChange: change,
			};
		},
	},

	{
		key: 'abandonments',
		label: __('Abandonments', 'everest-forms'),
		resolve: (data) =>
			fromMetric(
				data.pending_members,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
				true,
			),
	},
	{
		key: 'abandon_rate',
		label: __('Abandonment Rate', 'everest-forms'),
		resolve: (data) => {
			const incomplete = data.pending_members?.count ?? 0;
			const total = data.new_members?.count ?? 0;
			const prevIncomplete = data.pending_members?.previous ?? 0;
			const prevTotal = data.new_members?.previous ?? 0;
			const rate = total > 0 ? (incomplete / total) * 100 : 0;
			const prevRate = prevTotal > 0 ? (prevIncomplete / prevTotal) * 100 : 0;
			const change = prevRate > 0 ? ((rate - prevRate) / prevRate) * 100 : 0;
			return {
				value: `${rate.toFixed(1)}%`,
				pct: formatPct(change),
				pctClass: pctClass(change, true),
				cmp: `${__('vs.', 'everest-forms')} ${prevRate.toFixed(1)}% ${__('last period', 'everest-forms')}`,
				rawChange: change,
			};
		},
	},
];

const PAYMENT_STATS: StatDef[] = [
	{
		key: 'revenue',
		label: __('Revenue', 'everest-forms'),
		resolve: (data, currency) =>
			fromMetric(
				data.total_revenue,
				(v) => formatCurrency(v, currency),
				(m) =>
					`${__('vs.', 'everest-forms')} ${formatCurrency(m.previous, currency)} ${__('last period', 'everest-forms')}`,
			),
	},
	{
		key: 'transactions',
		label: __('Total Transactions', 'everest-forms'),
		resolve: (data) =>
			fromMetric(
				data.transactions,
				(v) => v.toLocaleString(),
				(m) =>
					`${__('vs.', 'everest-forms')} ${m.previous.toLocaleString()} ${__('last period', 'everest-forms')}`,
			),
	},
];

export const ALL_STATS: StatDef[] = [...GENERAL_STATS, ...PAYMENT_STATS];

export const DEFAULT_BOX_METRICS = [
	'total',
	'complete',
	'incomplete',
	'revenue',
];

type TimeUnit = 'hour' | 'day' | 'week' | 'month' | 'year';

interface ChartDataset {
	label: string;
	data: number[];
	borderColor?: string;
	backgroundColor?: string | string[];
	tension?: number;
}

interface ChartConfig {
	id: string;
	type: 'line' | 'pie' | 'bar' | 'list';
	title: string;
	labels: string[];
	datasets: ChartDataset[];
	pieCounts?: Array<{ label: string; value: number }>;
	listRows?: Array<{ label: string; value: number; url?: string }>;
}

interface SecondaryChartViewDef {
	value: string;
	label: string;
}

const SECONDARY_CHART_VIEWS: SecondaryChartViewDef[] = [
	{
		value: 'complete_vs_incomplete',
		label: __('Complete vs Incomplete', 'everest-forms'),
	},
	{ value: 'top_forms', label: __('Top Performing Forms', 'everest-forms') },
	{ value: 'device_breakdown', label: __('Device Breakdown', 'everest-forms') },
	{ value: 'revenue_overview', label: __('Revenue Overview', 'everest-forms') },
	{
		value: 'top_referral_pages',
		label: __('Top Referral Pages', 'everest-forms'),
	},
];

function getWeekNumber(d: Date): number {
	const date = new Date(d.getTime());
	date.setHours(0, 0, 0, 0);
	date.setDate(date.getDate() + 3 - ((date.getDay() + 6) % 7));
	const week1 = new Date(date.getFullYear(), 0, 4);
	return (
		1 +
		Math.round(
			((date.getTime() - week1.getTime()) / 86400000 -
				3 +
				((week1.getDay() + 6) % 7)) /
				7,
		)
	);
}

function generateLabels(start: Date, end: Date, unit: TimeUnit): string[] {
	const labels: string[] = [];
	const cur = new Date(start);
	const formatters: Record<TimeUnit, (d: Date) => string> = {
		hour: (d) =>
			d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
		day: (d) =>
			d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
		week: (d) => `Week ${getWeekNumber(d)}`,
		month: (d) =>
			d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
		year: (d) => d.getFullYear().toString(),
	};
	const inc: Record<TimeUnit, (d: Date) => void> = {
		hour: (d) => d.setHours(d.getHours() + 1),
		day: (d) => d.setDate(d.getDate() + 1),
		week: (d) => d.setDate(d.getDate() + 7),
		month: (d) => d.setMonth(d.getMonth() + 1),
		year: (d) => d.setFullYear(d.getFullYear() + 1),
	};
	while (cur <= end) {
		labels.push(formatters[unit](new Date(cur)));
		inc[unit](cur);
	}
	// Ensure the end date's label is always present (mirrors generatePeriodKeys fix).
	const endLabel = formatters[unit](end);
	if (labels[labels.length - 1] !== endLabel) {
		labels.push(endLabel);
	}
	return labels;
}

function generatePeriodKeys(start: Date, end: Date, unit: TimeUnit): string[] {
	const keys: string[] = [];
	const cur = new Date(start);
	const p = (n: number) => String(n).padStart(2, '0');
	const formatters: Record<TimeUnit, (d: Date) => string> = {
		hour: (d) =>
			`${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:00`,
		day: (d) => `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`,
		week: (d) => `${d.getFullYear()}-W${p(getWeekNumber(d))}`,
		month: (d) => `${d.getFullYear()}-${p(d.getMonth() + 1)}`,
		year: (d) => `${d.getFullYear()}`,
	};
	const inc: Record<TimeUnit, (d: Date) => void> = {
		hour: (d) => d.setHours(d.getHours() + 1),
		day: (d) => d.setDate(d.getDate() + 1),
		week: (d) => d.setDate(d.getDate() + 7),
		month: (d) => d.setMonth(d.getMonth() + 1),
		year: (d) => d.setFullYear(d.getFullYear() + 1),
	};
	while (cur <= end) {
		keys.push(formatters[unit](new Date(cur)));
		inc[unit](cur);
	}
	// The end date may fall in a later period than the last weekly/monthly increment
	// (e.g. Mar 9 is ISO Week 11 but the loop stops at Week 10 starting Mar 5).
	// Ensure the end date's period is always included so chart and count box totals match.
	const endKey = formatters[unit](end);
	if (keys[keys.length - 1] !== endKey) {
		keys.push(endKey);
	}
	return keys;
}

interface PrimaryChartViewDef {
	value: string;
	label: string;
	group: 'overview' | 'status' | 'payment';
	isPrimary?: boolean;
}

export const PRIMARY_CHART_VIEWS: PrimaryChartViewDef[] = [
	{
		value: 'submissions',
		label: __('Submissions Overview', 'everest-forms'),
		group: 'overview',
		isPrimary: true,
	},
	{ value: 'total', label: __('Total', 'everest-forms'), group: 'overview' },
	{
		value: 'completed',
		label: __('Completed', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'abandoned',
		label: __('Abandoned', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'bounced',
		label: __('Bounced', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'impressions',
		label: __('Impressions', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'revenue',
		label: __('Revenue', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'completion_rate',
		label: __('Completion Rate %', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'pending',
		label: __('Pending', 'everest-forms'),
		group: 'overview',
	},
	{
		value: 'entry_status',
		label: __('Entry Status', 'everest-forms'),
		group: 'status',
		isPrimary: true,
	},
	{ value: 'read', label: __('Read', 'everest-forms'), group: 'status' },
	{ value: 'unread', label: __('Unread', 'everest-forms'), group: 'status' },
	{ value: 'trash', label: __('Trashed', 'everest-forms'), group: 'status' },
	{ value: 'spam', label: __('Spam', 'everest-forms'), group: 'status' },
	{ value: 'pending', label: __('Pending', 'everest-forms'), group: 'status' },
	{
		value: 'payment_overview',
		label: __('Payment Overview', 'everest-forms'),
		group: 'payment',
		isPrimary: true,
	},
	{
		value: 'payment_complete',
		label: __('Paid', 'everest-forms'),
		group: 'payment',
	},
	{
		value: 'payment_pending',
		label: __('Pending', 'everest-forms'),
		group: 'payment',
	},
	{
		value: 'payment_failed',
		label: __('Failed', 'everest-forms'),
		group: 'payment',
	},
	{
		value: 'payment_refunded',
		label: __('Refunded', 'everest-forms'),
		group: 'payment',
	},
];

function buildPrimaryDatasets(
	view: string,
	sub: number[],
	comp: number[],
	incomp: number[],
	rev: number[],
	bounced: number[],
	impressions: number[],
	read: number[],
	unread: number[],
	trash: number[],
	spam: number[],
	pending: number[],
	payComp: number[],
	payPend: number[],
	payFail: number[],
	payRefund: number[],
	starred: number[],
): ChartDataset[] {
	switch (view) {
		case 'total':
			return [
				{
					label: __('Total', 'everest-forms'),
					data: sub,
					borderColor: 'rgb(99, 102, 241)',
					backgroundColor: 'rgba(99, 102, 241, 0.1)',
					tension: 0.2,
				},
			];
		case 'completed':
			return [
				{
					label: __('Completed', 'everest-forms'),
					data: comp,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
			];
		case 'incomplete':
			return [
				{
					label: __('Incomplete', 'everest-forms'),
					data: incomp,
					borderColor: 'rgb(234, 179, 8)',
					backgroundColor: 'rgba(234, 179, 8, 0.1)',
					tension: 0.2,
				},
			];
		case 'abandoned':
			return [
				{
					label: __('Abandoned', 'everest-forms'),
					data: incomp,
					borderColor: 'rgb(251, 146, 60)',
					backgroundColor: 'rgba(251, 146, 60, 0.1)',
					tension: 0.2,
				},
			];
		case 'bounced':
			return [
				{
					label: __('Bounced', 'everest-forms'),
					data: bounced,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
			];
		case 'impressions':
			return [
				{
					label: __('Impressions', 'everest-forms'),
					data: impressions,
					borderColor: 'rgb(148, 163, 184)',
					backgroundColor: 'rgba(148, 163, 184, 0.1)',
					tension: 0.2,
				},
			];
		case 'revenue':
			return [
				{
					label: __('Revenue', 'everest-forms'),
					data: rev,
					borderColor: 'rgb(75, 192, 192)',
					backgroundColor: 'rgba(75, 192, 192, 0.1)',
					tension: 0.2,
				},
			];
		case 'completion_rate':
			return [
				{
					label: __('Completion Rate %', 'everest-forms'),
					data: sub.map((t, i) =>
						t > 0 ? +((comp[i] / t) * 100).toFixed(1) : 0,
					),
					borderColor: 'rgb(99, 102, 241)',
					backgroundColor: 'rgba(99, 102, 241, 0.1)',
					tension: 0.2,
				},
			];
		case 'read':
			return [
				{
					label: __('Read', 'everest-forms'),
					data: read,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
			];
		case 'unread':
			return [
				{
					label: __('Unread', 'everest-forms'),
					data: unread,
					borderColor: 'rgb(59, 130, 246)',
					backgroundColor: 'rgba(59, 130, 246, 0.1)',
					tension: 0.2,
				},
			];
		case 'trash':
			return [
				{
					label: __('Trashed', 'everest-forms'),
					data: trash,
					borderColor: 'rgb(156, 163, 175)',
					backgroundColor: 'rgba(156, 163, 175, 0.1)',
					tension: 0.2,
				},
			];
		case 'spam':
			return [
				{
					label: __('Spam', 'everest-forms'),
					data: spam,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
			];
		case 'payment_overview':
			return [
				{
					label: __('Paid', 'everest-forms'),
					data: payComp,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Pending', 'everest-forms'),
					data: payPend,
					borderColor: 'rgb(234, 179, 8)',
					backgroundColor: 'rgba(234, 179, 8, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Failed', 'everest-forms'),
					data: payFail,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Refunded', 'everest-forms'),
					data: payRefund,
					borderColor: 'rgb(168, 85, 247)',
					backgroundColor: 'rgba(168, 85, 247, 0.1)',
					tension: 0.2,
				},
			];
		case 'payment_complete':
			return [
				{
					label: __('Paid', 'everest-forms'),
					data: payComp,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
			];
		case 'payment_pending':
			return [
				{
					label: __('Pending', 'everest-forms'),
					data: payPend,
					borderColor: 'rgb(234, 179, 8)',
					backgroundColor: 'rgba(234, 179, 8, 0.1)',
					tension: 0.2,
				},
			];
		case 'payment_failed':
			return [
				{
					label: __('Failed', 'everest-forms'),
					data: payFail,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
			];
		case 'payment_refunded':
			return [
				{
					label: __('Refunded', 'everest-forms'),
					data: payRefund,
					borderColor: 'rgb(168, 85, 247)',
					backgroundColor: 'rgba(168, 85, 247, 0.1)',
					tension: 0.2,
				},
			];
		case 'pending':
			return [
				{
					label: __('Pending', 'everest-forms'),
					data: pending,
					borderColor: 'rgb(249, 115, 22)',
					backgroundColor: 'rgba(249, 115, 22, 0.1)',
					tension: 0.2,
				},
			];
		case 'entry_status':
			return [
				{
					label: __('Read', 'everest-forms'),
					data: read,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Unread', 'everest-forms'),
					data: unread,
					borderColor: 'rgb(59, 130, 246)',
					backgroundColor: 'rgba(59, 130, 246, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Trashed', 'everest-forms'),
					data: trash,
					borderColor: 'rgb(156, 163, 175)',
					backgroundColor: 'rgba(156, 163, 175, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Spam', 'everest-forms'),
					data: spam,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Pending', 'everest-forms'),
					data: pending,
					borderColor: 'rgb(249, 115, 22)',
					backgroundColor: 'rgba(249, 115, 22, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Starred', 'everest-forms'),
					data: starred,
					borderColor: 'rgb(250, 204, 21)',
					backgroundColor: 'rgba(250, 204, 21, 0.1)',
					tension: 0.2,
				},
			];
		case 'submissions':
		default:
			return [
				{
					label: __('Total', 'everest-forms'),
					data: sub,
					borderColor: 'rgb(99, 102, 241)',
					backgroundColor: 'rgba(99, 102, 241, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Completed', 'everest-forms'),
					data: comp,
					borderColor: 'rgb(34, 197, 94)',
					backgroundColor: 'rgba(34, 197, 94, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Abandoned', 'everest-forms'),
					data: incomp,
					borderColor: 'rgb(251, 146, 60)',
					backgroundColor: 'rgba(251, 146, 60, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Bounced', 'everest-forms'),
					data: bounced,
					borderColor: 'rgb(239, 68, 68)',
					backgroundColor: 'rgba(239, 68, 68, 0.1)',
					tension: 0.2,
				},
				{
					label: __('Impressions', 'everest-forms'),
					data: impressions,
					borderColor: 'rgb(148, 163, 184)',
					backgroundColor: 'rgba(148, 163, 184, 0.1)',
					tension: 0.2,
				},
			];
	}
}

function buildSecondaryChart(
	view: string,
	overviewData: OverviewApiResponse,
	labels: string[],
	rev: number[],
	topFormsMetric: string = 'entries',
): ChartConfig {
	const complete = overviewData.approved_members?.count ?? 0;
	const incomplete = overviewData.pending_members?.count ?? 0;

	switch (view) {
		case 'complete_vs_incomplete':
			return {
				id: 'secondary-complete-incomplete',
				type: 'pie',
				title: __('Complete vs Incomplete', 'everest-forms'),
				labels: [
					__('Completed Entries', 'everest-forms'),
					__('Incomplete Entries', 'everest-forms'),
				],
				datasets: [
					{
						label: __('Entries', 'everest-forms'),
						data: [complete, incomplete],
						backgroundColor: ['#22c55e', '#fb923c'],
						// @ts-ignore
						borderColor: ['#22c55e', '#fb923c'],
					},
				],
				pieCounts: [
					{ label: __('Completed Entries', 'everest-forms'), value: complete },
					{
						label: __('Incomplete Entries', 'everest-forms'),
						value: incomplete,
					},
				],
			};

		case 'top_forms': {
			const topForms = [...(overviewData.top_forms ?? [])].sort((a, b) =>
				topFormsMetric === 'revenue'
					? b.revenue - a.revenue
					: b.count - a.count,
			);
			const isRevenue = topFormsMetric === 'revenue';
			return {
				id: 'secondary-top-forms',
				type: 'bar',
				title: __('Top Performing Forms', 'everest-forms'),
				labels: topForms.map((f) => f.title),
				datasets: [
					{
						label: isRevenue
							? __('Revenue', 'everest-forms')
							: __('Entries', 'everest-forms'),
						data: topForms.map((f) => (isRevenue ? f.revenue : f.count)),
						backgroundColor: isRevenue
							? 'rgba(16, 185, 129, 0.7)'
							: 'rgba(99, 102, 241, 0.7)',
						// @ts-ignore
						borderColor: isRevenue ? 'rgb(16, 185, 129)' : 'rgb(99, 102, 241)',
					},
				],
			};
		}

		case 'device_breakdown': {
			const db = overviewData.device_breakdown;
			const desktop = db?.desktop ?? 0;
			const mobile = db?.mobile ?? 0;
			const tablet = db?.tablet ?? 0;
			return {
				id: 'secondary-device-breakdown',
				type: 'pie',
				title: __('Device Breakdown', 'everest-forms'),
				labels: [
					__('Desktop', 'everest-forms'),
					__('Mobile', 'everest-forms'),
					__('Tablet', 'everest-forms'),
				],
				datasets: [
					{
						label: __('Entries', 'everest-forms'),
						data: [desktop, mobile, tablet],
						backgroundColor: ['#6366f1', '#22c55e', '#fb923c'],
						// @ts-ignore
						borderColor: ['#6366f1', '#22c55e', '#fb923c'],
					},
				],
				pieCounts: [
					{ label: __('Desktop', 'everest-forms'), value: desktop },
					{ label: __('Mobile', 'everest-forms'), value: mobile },
					{ label: __('Tablet', 'everest-forms'), value: tablet },
				],
			};
		}

		case 'top_referral_pages': {
			const pages = overviewData.top_referral_pages ?? [];
			return {
				id: 'secondary-referral-pages',
				type: 'list',
				title: __('Top Referral Pages', 'everest-forms'),
				labels: [],
				datasets: [],
				listRows: pages.map((p) => ({
					label: p.title,
					value: p.count,
					url: p.url,
				})),
			};
		}

		case 'revenue_overview':
		default:
			return {
				id: 'secondary-revenue',
				type: 'line',
				title: __('Revenue Overview', 'everest-forms'),
				labels,
				datasets: [
					{
						label: __('Revenue', 'everest-forms'),
						data: rev,
						borderColor: 'rgb(75, 192, 192)',
						backgroundColor: 'rgba(75, 192, 192, 0.1)',
						tension: 0.2,
					},
				],
			};
	}
}

function buildLiveCharts(
	timeSeries: TimeSeriesPoint[],
	overviewData: OverviewApiResponse,
	start: Date,
	end: Date,
	unit: TimeUnit,
	primaryView: string,
	secondaryViews: string[],
	topFormsMetric: string = 'entries',
): ChartConfig[] {
	const labels = generateLabels(start, end, unit);
	const periodKeys = generatePeriodKeys(start, end, unit);
	const byPeriod = new Map<string, TimeSeriesPoint>();
	for (const row of timeSeries) byPeriod.set(row.period, row);

	const sub = periodKeys.map((k) => byPeriod.get(k)?.total ?? 0);
	const comp = periodKeys.map((k) => byPeriod.get(k)?.complete ?? 0);
	const incomp = periodKeys.map((k) => byPeriod.get(k)?.incomplete ?? 0);
	const rev = periodKeys.map((k) => byPeriod.get(k)?.revenue ?? 0);
	const bounced = periodKeys.map((k) => byPeriod.get(k)?.bounced ?? 0);
	const impressions = periodKeys.map((k) => byPeriod.get(k)?.impressions ?? 0);
	const readEntries = periodKeys.map((k) => byPeriod.get(k)?.read ?? 0);
	const unreadEntries = periodKeys.map((k) => byPeriod.get(k)?.unread ?? 0);
	const trashEntries = periodKeys.map((k) => byPeriod.get(k)?.trash ?? 0);
	const spamEntries = periodKeys.map((k) => byPeriod.get(k)?.spam ?? 0);
	const pendingEntries = periodKeys.map((k) => byPeriod.get(k)?.pending ?? 0);
	const starredEntries = periodKeys.map((k) => byPeriod.get(k)?.starred ?? 0);
	const payComp = periodKeys.map((k) => byPeriod.get(k)?.payment_complete ?? 0);
	const payPend = periodKeys.map((k) => byPeriod.get(k)?.payment_pending ?? 0);
	const payFail = periodKeys.map((k) => byPeriod.get(k)?.payment_failed ?? 0);
	const payRefund = periodKeys.map(
		(k) => byPeriod.get(k)?.payment_refunded ?? 0,
	);

	const complete = overviewData.approved_members?.count ?? 0;
	const incomplete = overviewData.pending_members?.count ?? 0;
	const failed = overviewData.denied_members?.count ?? 0;

	return [
		{
			id: 'submissions-over-time',
			type: 'line',
			title:
				PRIMARY_CHART_VIEWS.find((v) => v.value === primaryView)?.label ??
				__('Submissions Overview', 'everest-forms'),
			labels,
			datasets: buildPrimaryDatasets(
				primaryView,
				sub,
				comp,
				incomp,
				rev,
				bounced,
				impressions,
				readEntries,
				unreadEntries,
				trashEntries,
				spamEntries,
				pendingEntries,
				payComp,
				payPend,
				payFail,
				payRefund,
				starredEntries,
			),
		},
		...secondaryViews.map((view) =>
			buildSecondaryChart(view, overviewData, labels, rev, topFormsMetric),
		),
	];
}

export const AnalyticsContent = ({
	overviewData,
	boxMetrics,
	selectedFormId,
	onBoxMetricChange,
	primaryChartView,
	secondaryViews,
	primaryChartType,
	onPrimaryChartViewChange,
	onSecondaryViewsChange,
	onPrimaryChartTypeChange,
}: AnalyticsContentProps) => {
	const { filters } = useAnalyticsFilters();
	const { dateFrom, dateTo, unit } = filters;

	const [topFormsMetric, setTopFormsMetric] = useState<'entries' | 'revenue'>(
		'entries',
	);

	useEffect(() => {
		if (selectedFormId && secondaryViews.includes('top_forms')) {
			onSecondaryViewsChange(
				secondaryViews.map((v) =>
					v === 'top_forms' ? 'complete_vs_incomplete' : v,
				),
			);
		}
	}, [selectedFormId]);

	const start = dateFrom
		? new Date(dateFrom)
		: new Date(new Date().setDate(new Date().getDate() - 30));
	const end = dateTo ? new Date(dateTo) : new Date();
	const timeUnit = unit ?? 'day';
	const currency = window.__EVF_ANALYTICS__?.currency ?? 'USD';

	const isPaymentForm =
		!!selectedFormId &&
		!!(overviewData.forms ?? []).find((f) => String(f.id) === selectedFormId)
			?.is_payment_form;

	useEffect(() => {
		if (!isPaymentForm) return;
		if (boxMetrics[boxMetrics.length - 1] !== 'revenue') {
			onBoxMetricChange(boxMetrics.length - 1, 'revenue');
		}
		if (secondaryViews[secondaryViews.length - 1] !== 'revenue_overview') {
			onSecondaryViewsChange([
				...secondaryViews.slice(0, -1),
				'revenue_overview',
			]);
		}
	}, [isPaymentForm]); // eslint-disable-line react-hooks/exhaustive-deps

	const charts = buildLiveCharts(
		overviewData?.time_series ?? [],
		overviewData,
		start,
		end,
		timeUnit,
		primaryChartView,
		secondaryViews,
		topFormsMetric,
	);

	return (
		<div className="EVF-Analytics-Content">
			<div className="EVF-Analytics-Metrics">
				{boxMetrics.map((statKey, index) => {
					const statDef = ALL_STATS.find((s) => s.key === statKey);
					if (!statDef) return null;

					const resolved = statDef.resolve(overviewData, currency);
					const isPositive = resolved.rawChange > 0;
					const isNegative = resolved.rawChange < 0;

					return (
						<div key={index} className="EVF-Analytics-Metric">
							<div className="EVF-Analytics-Metric__Header">
								<Select
									value={statKey}
									onValueChange={(val) => onBoxMetricChange(index, val)}
								>
									<SelectTrigger className="EVF-Analytics-Metric__MetricSelector">
										{statDef.label}
									</SelectTrigger>
									<SelectContent className="EVF-UI-Select-Content">
										<SelectLabel className="EVF-Analytics-Metric__GroupLabel">
											{__('General', 'everest-forms')}
										</SelectLabel>
										{GENERAL_STATS.map((s) => (
											<SelectItem
												key={s.key}
												value={s.key}
												className="EVF-Analytics-Metric__GroupItem"
												disabled={
													boxMetrics.includes(s.key) && s.key !== statKey
												}
											>
												{s.label}
											</SelectItem>
										))}
										<SelectLabel className="EVF-Analytics-Metric__GroupLabel">
											{__('Payments', 'everest-forms')}
										</SelectLabel>
										{PAYMENT_STATS.map((s) => (
											<SelectItem
												key={s.key}
												value={s.key}
												className="EVF-Analytics-Metric__GroupItem"
												disabled={
													boxMetrics.includes(s.key) && s.key !== statKey
												}
											>
												{s.label}
											</SelectItem>
										))}
									</SelectContent>
								</Select>
							</div>
							<div className="EVF-Analytics-Metric__Content">
								<div className="EVF-Analytics-Metric__Value">
									{resolved.value}
									<span
										className={cn(
											'EVF-Analytics-Metric__Delta',
											resolved.pctClass === 'positive' &&
												'EVF-Analytics-Metric__Delta--positive',
											resolved.pctClass === 'negative' &&
												'EVF-Analytics-Metric__Delta--negative',
										)}
									>
										{resolved.rawChange > 0
											? '▲'
											: resolved.rawChange < 0
												? '▼'
												: ''}
										{resolved.rawChange !== 0 && ' '}
										{resolved.pct}
									</span>
								</div>
								<div className="EVF-Analytics-Metric__Comparison">
									{resolved.cmp}
								</div>
							</div>
						</div>
					);
				})}
			</div>

			<div className="EVF-Analytics-Upgrade">
				<div className="EVF-Analytics-Charts">
					{charts.map((chart, i) => {
						const height = i === 0 ? 400 : 300;
						const secondaryKey =
							i > 0 ? (secondaryViews[i - 1] ?? '') : primaryChartView;
						const chartKey = `${i}-${dateFrom ?? 'default'}-${dateTo ?? 'default'}-${timeUnit}-${secondaryKey}`;
						const ChartComponent =
							i === 0
								? primaryChartType === 'bar'
									? Bar
									: Line
								: chart.type === 'line'
									? Line
									: chart.type === 'bar'
										? Bar
										: Pie;
						return (
							<div
								key={chartKey}
								className={cn(
									'EVF-Analytics-Chart',
									'EVF-Analytics-Chart--Multi',
									i === 0 && 'EVF-Analytics-Chart--Primary',
								)}
							>
								<div className="EVF-Analytics-Chart__Header">
									<div className="EVF-Analytics-Chart__ParentSelector">
										{i === 0 ? (
											<Select
												value={primaryChartView}
												onValueChange={onPrimaryChartViewChange}
											>
												<SelectTrigger className="EVF-Analytics-Chart__ChartSelector">
													{
														PRIMARY_CHART_VIEWS.find(
															(v) => v.value === primaryChartView,
														)?.label
													}
												</SelectTrigger>
												<SelectContent className="EVF-UI-Select-Content">
													{PRIMARY_CHART_VIEWS.filter(
														(v) => v.group === 'overview',
													).map((v) => (
														<SelectItem
															key={v.value}
															value={v.value}
															className={
																v.isPrimary
																	? 'EVF-Analytics-Chart__PrimaryOption'
																	: 'EVF-Analytics-Chart__SubOption'
															}
														>
															{v.label}
														</SelectItem>
													))}
													{PRIMARY_CHART_VIEWS.filter(
														(v) => v.group === 'status',
													).map((v) => (
														<SelectItem
															key={v.value}
															value={v.value}
															className={
																v.isPrimary
																	? 'EVF-Analytics-Chart__PrimaryOption'
																	: 'EVF-Analytics-Chart__SubOption'
															}
														>
															{v.label}
														</SelectItem>
													))}
													{PRIMARY_CHART_VIEWS.filter(
														(v) => v.group === 'payment',
													).map((v) => (
														<SelectItem
															key={v.value}
															value={v.value}
															className={
																v.isPrimary
																	? 'EVF-Analytics-Chart__PrimaryOption'
																	: 'EVF-Analytics-Chart__SubOption'
															}
														>
															{v.label}
														</SelectItem>
													))}
												</SelectContent>
											</Select>
										) : (
											<Select
												value={secondaryViews[i - 1]}
												onValueChange={(val) => {
													const next = [...secondaryViews];
													next[i - 1] = val;
													onSecondaryViewsChange(next);
												}}
											>
												<SelectTrigger className="EVF-Analytics-Chart__ChartSelector">
													{
														SECONDARY_CHART_VIEWS.find(
															(v) => v.value === secondaryViews[i - 1],
														)?.label
													}
												</SelectTrigger>
												<SelectContent className="EVF-UI-Select-Content">
													{SECONDARY_CHART_VIEWS.filter(
														(v) => v.value !== 'top_forms' || !selectedFormId,
													).map((v) => (
														<SelectItem
															key={v.value}
															value={v.value}
															disabled={
																secondaryViews.includes(v.value) &&
																secondaryViews[i - 1] !== v.value
															}
														>
															{v.label}
														</SelectItem>
													))}
												</SelectContent>
											</Select>
										)}
									</div>
									<div className="EVF-Analytics-Chart__HeaderRight">
										{i > 0 && secondaryViews[i - 1] === 'top_forms' && (
											<div className="EVF-Analytics-Chart__TypeToggle">
												<button
													type="button"
													className={cn(
														'EVF-Analytics-Chart__TypeBtn',
														'EVF-Analytics-Chart__TypeBtn--label',
														topFormsMetric === 'entries' &&
															'EVF-Analytics-Chart__TypeBtn--active',
													)}
													onClick={() => setTopFormsMetric('entries')}
												>
													{__('Entries', 'everest-forms')}
												</button>
												<button
													type="button"
													className={cn(
														'EVF-Analytics-Chart__TypeBtn',
														'EVF-Analytics-Chart__TypeBtn--label',
														topFormsMetric === 'revenue' &&
															'EVF-Analytics-Chart__TypeBtn--active',
													)}
													onClick={() => setTopFormsMetric('revenue')}
												>
													{__('Revenue', 'everest-forms')}
												</button>
											</div>
										)}
										<div
											id={`EVF-Analytics-Legend-Container-${i}`}
											className={cn(
												'EVF-Analytics-Chart__HTMLLegend',
												i === 0 && 'EVF-Analytics-Chart__HTMLLegend--Primary',
											)}
										/>
										{i === 0 && (
											<div className="EVF-Analytics-Chart__TypeToggle">
												<button
													type="button"
													className={cn(
														'EVF-Analytics-Chart__TypeBtn',
														primaryChartType === 'line' &&
															'EVF-Analytics-Chart__TypeBtn--active',
													)}
													onClick={() => onPrimaryChartTypeChange('line')}
													title={__('Line chart', 'everest-forms')}
												>
													<svg
														xmlns="http://www.w3.org/2000/svg"
														width="14"
														height="14"
														viewBox="0 0 14 14"
														fill="none"
														stroke="currentColor"
														strokeWidth="1.16667"
														strokeLinecap="round"
														strokeLinejoin="round"
													>
														<path d="M5.83337 5.25001L8.75004 2.33334L11.6667 5.25001" />
														<path d="M2.33337 11.6667H6.41671C7.03555 11.6667 7.62904 11.4208 8.06662 10.9833C8.50421 10.5457 8.75004 9.95218 8.75004 9.33334V2.33334" />
													</svg>
												</button>
												<button
													type="button"
													className={cn(
														'EVF-Analytics-Chart__TypeBtn',
														primaryChartType === 'bar' &&
															'EVF-Analytics-Chart__TypeBtn--active',
													)}
													onClick={() => onPrimaryChartTypeChange('bar')}
													title={__('Bar chart', 'everest-forms')}
												>
													<svg
														xmlns="http://www.w3.org/2000/svg"
														width="14"
														height="14"
														viewBox="0 0 14 14"
														fill="none"
														stroke="currentColor"
														strokeWidth="1.16667"
														strokeLinecap="round"
														strokeLinejoin="round"
													>
														<path d="M1.75 1.75V11.0833C1.75 11.3928 1.87292 11.6895 2.09171 11.9083C2.3105 12.1271 2.60725 12.25 2.91667 12.25H12.25" />
														<path d="M10.5 9.91667V5.25" />
														<path d="M7.58337 9.91666V2.91666" />
														<path d="M4.66663 9.91666V8.16666" />
													</svg>
												</button>
											</div>
										)}
									</div>
								</div>
								<div
									className="EVF-Analytics-Chart__Content"
									style={{ height: `${height}px` }}
								>
									{chart.type === 'list' ? (
										<div className="EVF-Analytics-Referral">
											{(chart.listRows?.length ?? 0) > 0 ? (
												chart.listRows!.map((row, idx) => {
													const max = chart.listRows![0]?.value || 1;
													const pct = Math.round((row.value / max) * 100);
													return (
														<div
															key={idx}
															className="EVF-Analytics-Referral__Row"
														>
															<div className="EVF-Analytics-Referral__Info">
																{!row.url || row.url === 'Direct / (none)' ? (
																	<span
																		className="EVF-Analytics-Referral__URL"
																		title={row.label}
																	>
																		{row.label}
																	</span>
																) : (
																	<a
																		className="EVF-Analytics-Referral__URL"
																		href={row.url}
																		title={row.url}
																		target="_blank"
																		rel="noopener noreferrer"
																	>
																		<span className="EVF-Analytics-Referral__URLText">
																			{row.label}
																		</span>
																		<ExternalLink size={12} />
																	</a>
																)}
																<div className="EVF-Analytics-Referral__Bar">
																	<div
																		className="EVF-Analytics-Referral__Fill"
																		style={{ width: `${pct}%` }}
																	/>
																</div>
															</div>
															<span className="EVF-Analytics-Referral__Count">
																{row.value.toLocaleString()}
															</span>
														</div>
													);
												})
											) : (
												<div className="EVF-Analytics-Chart__Empty">
													{__('No referral data available.', 'everest-forms')}
												</div>
											)}
										</div>
									) : (
										<div className="EVF-Analytics-Chart__ChartContainer">
											<ChartComponent
												height={height}
												data={{
													labels: chart.labels,
													datasets:
														i === 0 && primaryChartType === 'bar'
															? chart.datasets.map((ds) => ({
																	...ds,
																	backgroundColor: ds.borderColor,
																}))
															: chart.datasets,
												}}
												options={{
													responsive: true,
													maintainAspectRatio: false,
													plugins: {
														// @ts-ignore
														HTMLLegend: {
															containerID: `EVF-Analytics-Legend-Container-${i}`,
														},
														legend: { display: false },
														tooltip: { enabled: true },
													},
													...(ChartComponent !== Pie && {
														scales: {
															x: { grid: { display: false } },
															y: { grid: { display: false } },
														},
													}),
												}}
											/>
										</div>
									)}
								</div>
								{chart.pieCounts && chart.pieCounts.length > 0 && (
									<div className="EVF-Analytics-Chart__PieCounts">
										{chart.pieCounts.map((c) => (
											<div
												key={c.label}
												className="EVF-Analytics-Chart__PieCount"
											>
												<span className="EVF-Analytics-Chart__PieCount-Value">
													{c.value.toLocaleString()}
												</span>
												<span className="EVF-Analytics-Chart__PieCount-Label">
													{c.label}
												</span>
											</div>
										))}
									</div>
								)}
							</div>
						);
					})}
				</div>
			</div>
		</div>
	);
};
