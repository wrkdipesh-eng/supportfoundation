import { useQueries } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { format } from 'date-fns';
import { useCallback, useEffect, useState } from 'react';
import {
	AnalyticsContent,
	DEFAULT_BOX_METRICS,
} from './components/analytics-content';
import { Download } from './components/icons/download';
import { Printer } from './components/icons/printer';
import { Layout, LayoutBody, LayoutHeader } from './components/layout';
import { DateRangePicker } from './components/ui/date-range-picker';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
} from './components/ui/select';
import { DATE_UNITS } from './constants/configs';
import { useAnalyticsFilters } from './hooks/use-analytics-filters';
import { OverviewApiResponse } from './types/analytics';

const API_BASE_PATH = '/everest-forms-pro/v1/analytics';

const buildQueryString = (filters: Record<string, any>): string => {
	return Object.entries(filters)
		.map(([key, value]) => {
			if (!value) return null;
			const snakeKey = key.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();
			return `${snakeKey}=${encodeURIComponent(value)}`;
		})
		.filter(Boolean)
		.join('&');
};

const ProAnalytics = () => {
	const { setFilters, dateFrom, dateTo, filters, validUnits } =
		useAnalyticsFilters();

	const [selectedFormId, setSelectedFormId] = useState<string>(() => {
		const params = new URLSearchParams(window.location.search);
		return params.get('form_id') || '';
	});
	const [boxMetrics, setBoxMetrics] = useState<string[]>(DEFAULT_BOX_METRICS);
	const [primaryChartView, setPrimaryChartView] =
		useState<string>('submissions');
	const [secondaryViews, setSecondaryViews] = useState<string[]>([
		'complete_vs_incomplete',
		'top_forms',
		'device_breakdown',
	]);
	const [primaryChartType, setPrimaryChartType] = useState<'line' | 'bar'>(
		'line',
	);

	const [overviewQuery, preferencesQuery] = useQueries({
		queries: [
			{
				queryKey: ['overview', filters, selectedFormId],
				queryFn: async () => {
					try {
						const queryString = buildQueryString({
							...filters,
							formId: selectedFormId || undefined,
						});
						return await apiFetch<OverviewApiResponse>({
							path: `${API_BASE_PATH}?${queryString}`,
						});
					} catch (error) {
						console.warn('API failed, using mock data:', error);
					}
				},
				retry: 1,
				staleTime: 30000,
				keepPreviousData: true,
			},
			{
				queryKey: ['analytics-preferences'],
				queryFn: async () => {
					return await apiFetch<{
						box_metrics: string[];
						chart_prefs?: {
							primary_view: string;
							secondary_views: string[];
							chart_type: 'line' | 'bar';
						};
					}>({
						path: `${API_BASE_PATH}/preferences`,
					});
				},
				staleTime: Infinity,
			},
		],
	});

	useEffect(() => {
		if (preferencesQuery.data?.box_metrics) {
			setBoxMetrics(preferencesQuery.data.box_metrics);
		}
		if (preferencesQuery.data?.chart_prefs) {
			const cp = preferencesQuery.data.chart_prefs;
			if (cp.secondary_views?.length === 3)
				setSecondaryViews(cp.secondary_views);
		}
	}, [preferencesQuery.data]);

	const saveBoxMetrics = useCallback(async (metrics: string[]) => {
		try {
			await apiFetch({
				path: `${API_BASE_PATH}/preferences`,
				method: 'POST',
				data: { box_metrics: metrics },
			});
		} catch (error) {
			console.warn('Failed to save preferences:', error);
		}
	}, []);

	const saveSecondaryViews = useCallback(async (views: string[]) => {
		try {
			await apiFetch({
				path: `${API_BASE_PATH}/preferences`,
				method: 'POST',
				data: {
					chart_prefs: {
						secondary_views: views,
					},
				},
			});
		} catch (error) {
			console.warn('Failed to save chart prefs:', error);
		}
	}, []);

	const handlePrimaryChartViewChange = useCallback((view: string) => {
		setPrimaryChartView(view);
	}, []);

	const handleSecondaryViewsChange = useCallback(
		(views: string[]) => {
			setSecondaryViews(views);
			saveSecondaryViews(views);
		},
		[saveSecondaryViews],
	);

	const handlePrimaryChartTypeChange = useCallback((type: 'line' | 'bar') => {
		setPrimaryChartType(type);
	}, []);

	const handleBoxMetricChange = useCallback(
		async (index: number, value: string) => {
			if (boxMetrics.includes(value) && boxMetrics[index] !== value) return;
			const next = [...boxMetrics];
			next[index] = value;
			setBoxMetrics(next);
			await saveBoxMetrics(next);
		},
		[boxMetrics, saveBoxMetrics],
	);

	const handleDateRangeUpdate = useCallback(
		(values: { range: { from?: Date; to?: Date } }) => {
			if (!values.range.from || !values.range.to) return;

			setFilters({
				dateFrom: format(values.range.from, 'yyyy-MM-dd'),
				dateTo: format(values.range.to, 'yyyy-MM-dd'),
			});
		},
		[setFilters],
	);

	const handleUnitChange = useCallback(
		(value: string) => {
			setFilters({
				unit: value as 'hour' | 'day' | 'week' | 'month' | 'year' | undefined,
			});
		},
		[setFilters],
	);

	const handlePrint = useCallback(() => {
		const bodyEl = document.querySelector<HTMLElement>(
			'.EVF-Analytics-Layout-Body',
		);
		if (!bodyEl) return;

		const parentNode = bodyEl.parentNode;
		const nextSibling = bodyEl.nextSibling;

		const forms = overviewQuery.data?.forms ?? [];
		const selectedForm = forms.find(
			(f: any) => String(f.id) === selectedFormId,
		);
		const formLabel = selectedForm?.title ?? __('All Forms', 'everest-forms');

		const effectiveFrom =
			filters.dateFrom ??
			(() => {
				const d = new Date();
				d.setDate(d.getDate() - 30);
				return format(d, 'yyyy-MM-dd');
			})();
		const effectiveTo = filters.dateTo ?? format(new Date(), 'yyyy-MM-dd');
		const dateLabel = `${effectiveFrom} – ${effectiveTo}`;
		const filterSpans = [formLabel, dateLabel]
			.filter(Boolean)
			.map((t) => `<span>${t}</span>`)
			.join('');

		const printArea = document.createElement('div');
		printArea.id = 'evf-analytics-print-area';
		printArea.innerHTML =
			'<div class="EVF-Print-Logo">' +
			'<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
			'<path d="M30.517 6.188h-6.69l2.05 3.453h6.69zm4.209 6.906h-6.69l2.157 3.453h6.69zm-.108 17.266H9.15l10.683-17.482L24.258 20h-4.424l-2.05 3.453h12.625L19.834 6.403 3 33.813h33.776z" fill="#5317aa"/>' +
			'</svg>' +
			`<div class="EVF-Print-Filters">${filterSpans}</div>` +
			'</div>';

		printArea.appendChild(bodyEl);
		document.body.appendChild(printArea);

		const restore = () => {
			if (parentNode) {
				parentNode.insertBefore(bodyEl, nextSibling);
			}
			printArea.parentNode?.removeChild(printArea);
			window.removeEventListener('afterprint', restore);
		};
		window.addEventListener('afterprint', restore);

		window.print();
	}, [overviewQuery.data, selectedFormId, filters]);

	const handleExport = useCallback(() => {
		const data = overviewQuery.data;
		if (!data) return;

		const forms = data.forms ?? [];
		const selectedForm = forms.find(
			(f: any) => String(f.id) === selectedFormId,
		);
		const formLabel = selectedForm?.title ?? __('All Forms', 'everest-forms');
		const isPaymentForm = selectedForm?.is_payment_form ?? !selectedFormId;

		const rows: string[] = [];

		const esc = (v: string | number) => {
			const s = String(v);
			return s.includes(',') || s.includes('"') || s.includes('\n')
				? `"${s.replace(/"/g, '""')}"`
				: s;
		};

		const fmtPct = (v: number) => {
			if (v === 0) return '0%';
			return (v > 0 ? '+' : '') + v.toFixed(1) + '%';
		};

		rows.push(__('Analytics Report', 'everest-forms'));
		rows.push(`${__('Form', 'everest-forms')},${esc(formLabel)}`);
		rows.push(
			`${__('Date Range', 'everest-forms')},${esc(filters.dateFrom ?? '')} – ${esc(filters.dateTo ?? '')}`,
		);
		rows.push(
			`${__('Unit', 'everest-forms')},${esc(DATE_UNITS.find((u) => u.value === filters.unit)?.label ?? filters.unit ?? '')}`,
		);
		rows.push('');

		rows.push(__('Summary Metrics', 'everest-forms'));
		rows.push(
			`${__('Metric', 'everest-forms')},${__('Current Period', 'everest-forms')},${__('Previous Period', 'everest-forms')},${__('Change', 'everest-forms')}`,
		);

		const coreMetricRows: Array<[string, any]> = [
			[__('Total Submissions', 'everest-forms'), data.new_members],
			[__('Complete Submissions', 'everest-forms'), data.approved_members],
			[__('Incomplete Submissions', 'everest-forms'), data.pending_members],
			[__('Unique Respondents', 'everest-forms'), data.unique_respondents],
		];

		const paymentMetricRows: Array<[string, any]> = [
			[__('Total Revenue', 'everest-forms'), data.total_revenue],
			[__('Average Order Value', 'everest-forms'), data.average_order_value],
			[__('Refunded Revenue', 'everest-forms'), data.refunded_revenue],
			[__('Transactions', 'everest-forms'), data.transactions],
		];

		const allMetricRows = isPaymentForm
			? [...coreMetricRows, ...paymentMetricRows]
			: coreMetricRows;

		allMetricRows.forEach(([label, metric]) => {
			if (!metric) return;
			rows.push(
				`${esc(label)},${esc(metric.count)},${esc(metric.previous)},${fmtPct(metric.percentage_change)}`,
			);
		});

		rows.push('');

		if (data.device_breakdown) {
			rows.push(__('Device Breakdown', 'everest-forms'));
			rows.push(
				`${__('Device', 'everest-forms')},${__('Count', 'everest-forms')}`,
			);
			rows.push(
				`${__('Desktop', 'everest-forms')},${esc(data.device_breakdown.desktop)}`,
			);
			rows.push(
				`${__('Mobile', 'everest-forms')},${esc(data.device_breakdown.mobile)}`,
			);
			rows.push(
				`${__('Tablet', 'everest-forms')},${esc(data.device_breakdown.tablet)}`,
			);
			rows.push('');
		}

		rows.push(__('Submissions Over Time', 'everest-forms'));
		const tsHeaders = [
			__('Period', 'everest-forms'),
			__('Total', 'everest-forms'),
			__('Complete', 'everest-forms'),
			__('Incomplete', 'everest-forms'),
			...(isPaymentForm
				? [
						__('Revenue', 'everest-forms'),
						__('Payment Complete', 'everest-forms'),
						__('Payment Pending', 'everest-forms'),
						__('Payment Failed', 'everest-forms'),
						__('Payment Refunded', 'everest-forms'),
					]
				: []),
		];
		rows.push(tsHeaders.map(esc).join(','));

		(data.time_series ?? []).forEach((point) => {
			const cols = [
				esc(point.period),
				esc(point.total),
				esc(point.complete),
				esc(point.incomplete),
				...(isPaymentForm
					? [
							esc(point.revenue),
							esc(point.payment_complete ?? 0),
							esc(point.payment_pending ?? 0),
							esc(point.payment_failed ?? 0),
							esc(point.payment_refunded ?? 0),
						]
					: []),
			];
			rows.push(cols.join(','));
		});

		const csv = '\uFEFF' + rows.join('\n');
		const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
		const url = URL.createObjectURL(blob);
		const link = document.createElement('a');
		const dateStr = format(new Date(), 'yyyy-MM-dd');
		link.href = url;
		link.download = `analytics-${dateStr}.csv`;
		link.click();
		URL.revokeObjectURL(url);
	}, [overviewQuery.data, selectedFormId, filters]);

	const isInitialLoading =
		overviewQuery.isLoading || preferencesQuery.isLoading;
	const isFetching = overviewQuery.isFetching;
	const isLoading = isInitialLoading || isFetching;
	const hasError = overviewQuery.error && !overviewQuery.data;
	const hasData = !!overviewQuery.data;

	const getErrorMessage = () => {
		const errors: string[] = [];

		if (overviewQuery.error) {
			const errorMessage =
				overviewQuery.error instanceof Error
					? overviewQuery.error.message
					: typeof overviewQuery.error === 'object' &&
						  overviewQuery.error !== null &&
						  'message' in overviewQuery.error
						? String(overviewQuery.error)
						: __('Failed to load analytics overview data.', 'everest-forms');
			errors.push(errorMessage);
		}

		return errors.length > 0
			? errors.join(' ')
			: __('Failed to load analytics data.', 'everest-forms');
	};

	return (
		<Layout>
			<LayoutHeader>
				<div className="EVF-Analytics-Header-Content">
					<div className="EVF-Analytics-Filters">
						<div>
							{(() => {
								const forms = overviewQuery.data?.forms ?? [];
								const selectedForm = forms.find(
									(f: any) => String(f.id) === selectedFormId,
								);
								return (
									<Select
										value={selectedFormId}
										onValueChange={setSelectedFormId}
									>
										<SelectTrigger disabled={isLoading}>
											{selectedForm?.title ?? __('All Forms', 'everest-forms')}
										</SelectTrigger>
										<SelectContent className="EVF-UI-Select-Content">
											{selectedFormId && (
												<SelectItem value="">
													{__('All Forms', 'everest-forms')}
												</SelectItem>
											)}
											{forms.map((form: any) => (
												<SelectItem key={form.id} value={String(form.id)}>
													{form.title}
												</SelectItem>
											))}
										</SelectContent>
									</Select>
								);
							})()}
						</div>
						<div>
							<DateRangePicker
								align="start"
								initialDateFrom={dateFrom}
								initialDateTo={dateTo}
								onUpdate={handleDateRangeUpdate}
								disabled={isLoading}
							/>
						</div>
						<div>
							<Select value={filters.unit} onValueChange={handleUnitChange}>
								<SelectTrigger disabled={isLoading}>
									{DATE_UNITS.find((u) => u.value === filters.unit)?.label ??
										__('Day', 'everest-forms')}
								</SelectTrigger>
								<SelectContent
									className="EVF-UI-Select-Content"
									position="popper"
								>
									{DATE_UNITS.filter((unit) =>
										validUnits.includes(unit.value),
									).map((unit) => (
										<SelectItem key={unit.value} value={unit.value}>
											{unit.label}
										</SelectItem>
									))}
								</SelectContent>
							</Select>
						</div>
						<div className="EVF-Analytics-Actions EVF-Analytics-Actions--no-print">
							{selectedFormId &&
								(overviewQuery.data?.forms ?? []).find(
									(f: any) => String(f.id) === selectedFormId,
								)?.is_survey_form && (
									<a
										className="EVF-Analytics-Action-Btn EVF-Analytics-Action-Btn--outline"
										href={`admin.php?page=evf-entries&view=all-report&form-id=${selectedFormId}&source=analytics`}
										title={__('View Survey Report', 'everest-forms')}
									>
										<svg
											width="13"
											height="13"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										>
											<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
											<polyline points="14 2 14 8 20 8" />
											<line x1="16" y1="13" x2="8" y2="13" />
											<line x1="16" y1="17" x2="8" y2="17" />
											<polyline points="10 9 9 9 8 9" />
										</svg>
										<span>{__('Survey Report', 'everest-forms')}</span>
									</a>
								)}
							<button
								className="EVF-Analytics-Action-Btn EVF-Analytics-Action-Btn--outline"
								onClick={handleExport}
								disabled={isLoading || !hasData}
								title={__('Export as CSV', 'everest-forms')}
							>
								<Download size={13} />
								<span>{__('Export', 'everest-forms')}</span>
							</button>
							<button
								className="EVF-Analytics-Action-Btn EVF-Analytics-Action-Btn--outline"
								onClick={handlePrint}
								disabled={isLoading || !hasData}
								title={__('Print analytics', 'everest-forms')}
							>
								<Printer size={13} />
								<span>{__('Print', 'everest-forms')}</span>
							</button>
						</div>
					</div>
				</div>
			</LayoutHeader>
			<LayoutBody>
				{isInitialLoading ? (
					<div className="EVF-Analytics-Skeleton">
						<div className="EVF-Analytics-Skeleton__Metrics">
							{[0, 1, 2, 3].map((i) => (
								<div key={i} className="EVF-Analytics-Skeleton__Metric">
									<div className="EVF-Analytics-Skeleton__MetricLabel" />
									<div className="EVF-Analytics-Skeleton__MetricValue" />
									<div className="EVF-Analytics-Skeleton__MetricDelta" />
								</div>
							))}
						</div>
						<div className="EVF-Analytics-Skeleton__Charts">
							<div className="EVF-Analytics-Skeleton__Chart EVF-Analytics-Skeleton__Chart--primary">
								<div className="EVF-Analytics-Skeleton__ChartHeader">
									<div className="EVF-Analytics-Skeleton__ChartHeaderLeft">
										<div className="EVF-Analytics-Skeleton__ChartTitle" />
										<div className="EVF-Analytics-Skeleton__ChartSubtitle" />
									</div>
									<div className="EVF-Analytics-Skeleton__ChartToggle" />
								</div>
								<div className="EVF-Analytics-Skeleton__ChartLegend">
									<div className="EVF-Analytics-Skeleton__ChartLegendItem" />
									<div className="EVF-Analytics-Skeleton__ChartLegendItem" />
								</div>
								<div className="EVF-Analytics-Skeleton__ChartCanvas" />
							</div>
							{[0, 1, 2].map((i) => (
								<div
									key={i}
									className="EVF-Analytics-Skeleton__Chart EVF-Analytics-Skeleton__Chart--secondary"
								>
									<div className="EVF-Analytics-Skeleton__ChartHeader">
										<div className="EVF-Analytics-Skeleton__ChartHeaderLeft">
											<div className="EVF-Analytics-Skeleton__ChartTitle" />
										</div>
									</div>
									<div className="EVF-Analytics-Skeleton__ChartCanvas" />
								</div>
							))}
						</div>
					</div>
				) : hasError ? (
					<div className="EVF-Analytics-Error">
						<div className="EVF-Analytics-Error-Icon">⚠️</div>
						<p className="EVF-Analytics-Error-Message">{getErrorMessage()}</p>
						<button
							className="EVF-Analytics-Error-Retry"
							onClick={() => {
								overviewQuery.refetch();
							}}
						>
							{__('Retry', 'everest-forms')}
						</button>
					</div>
				) : hasData ? (
					<div style={{ position: 'relative' }}>
						{isFetching && <div className="EVF-Analytics-Refetch-Indicator" />}
						<AnalyticsContent
							overviewData={overviewQuery.data}
							boxMetrics={boxMetrics}
							selectedFormId={selectedFormId}
							onBoxMetricChange={handleBoxMetricChange}
							primaryChartView={primaryChartView}
							secondaryViews={secondaryViews}
							primaryChartType={primaryChartType}
							onPrimaryChartViewChange={handlePrimaryChartViewChange}
							onSecondaryViewsChange={handleSecondaryViewsChange}
							onPrimaryChartTypeChange={handlePrimaryChartTypeChange}
						/>
					</div>
				) : (
					<div className="EVF-Analytics-Error">
						<p>{__('Failed to load analytics data.', 'everest-forms')}</p>
					</div>
				)}
			</LayoutBody>
		</Layout>
	);
};

export default ProAnalytics;
