import { ChartData } from 'chart.js';
import { useMemo } from 'react';
import { CHART_COLORS } from '../constants/configs';
import {
	OverviewApiResponse,
	PreferencesApiResponse,
} from '../types/pro-analytics';
import { ChartCard } from './chart-card';
import { ListCard } from './list-card';
import { MetricCard } from './metric-card';

const REFUNDED_REVENUE_SLUG = 'refunded-revenue';

const globalColorMap = new Map<string, string>();
let globalColorIndex = 0;

const getColorForMetric = (slug: string): string => {
	if (slug === REFUNDED_REVENUE_SLUG) {
		return '#EF4444';
	}
	if (!globalColorMap.has(slug)) {
		globalColorMap.set(
			slug,
			CHART_COLORS[globalColorIndex % CHART_COLORS.length],
		);
		globalColorIndex++;
	}
	return globalColorMap.get(slug) || CHART_COLORS[0];
};

interface ProAnalyticsContentProps {
	overviewData: OverviewApiResponse;
	preferencesData: PreferencesApiResponse;
	onMetricChange: (index: number, metric: string) => void;
	onVisualizationChange: (index: number, visualization: string) => void;
	getVisualizationOptions: () => Array<{
		label: string;
		value: string;
		indent: boolean;
		isPie: boolean;
		isList: boolean;
	}>;
	getVisualizationChartType: (slug: string) => string;
}

export const ProAnalyticsContent = ({
	overviewData,
	preferencesData,
	onMetricChange,
	onVisualizationChange,
	getVisualizationOptions,
	getVisualizationChartType,
}: ProAnalyticsContentProps) => {
	const summaryPreference = preferencesData.summary;
	const visualizationPreference = preferencesData.visualization;
	const charts = overviewData.props.overview.charts;

	const chartDataGroups = useMemo(() => {
		let data: Record<string, ChartData> = {};

		for (const v of window.__EVF_ANALYTICS__.data_sets.visualization) {
			if (v.metrics.length > 1) {
				for (let i = 0; i < v.metrics.length; i++) {
					const metric = v.metrics[i];
					if (!charts.some((x) => x.slug === metric.slug)) continue;
					const chart = charts.find((x) => x.slug === metric.slug)!;
					data[v.slug] = {
						labels: chart.data.map((x) =>
							'time' in x ? x.time || '' : x.label || '',
						),
						datasets: v.metrics.map((m: { slug: string; label: string }) => {
							const color = getColorForMetric(m.slug);
							return {
								label: m.label,
								data:
									charts
										.find((x) => x.slug === m.slug)
										?.data.map((x) => x.value) ?? [],
								borderColor: color,
								backgroundColor: color,
								fill: false,
								tension: 0.2,
							};
						}),
					};
				}
			} else {
				if (charts.some((x) => x.slug === v.slug)) {
					const chart = charts.find((x) => x.slug === v.slug)!;
					const isPieChart = v?.type === 'pie';

					if (isPieChart) {
						const pieColors = chart.data.map((_, idx) =>
							getColorForMetric(`${v.slug}-${idx}`),
						);
						const hexToRgba = (hex: string, alpha: number) => {
							const r = parseInt(hex.slice(1, 3), 16);
							const g = parseInt(hex.slice(3, 5), 16);
							const b = parseInt(hex.slice(5, 7), 16);
							return `rgba(${r}, ${g}, ${b}, ${alpha})`;
						};
						data[v.slug] = {
							labels: chart.data.map((x) =>
								'time' in x ? x.time || '' : x.label || '',
							),
							datasets: [
								{
									label: v.label,
									data: chart.data?.map((x) => x.value) ?? [],
									backgroundColor: pieColors,
									borderColor: pieColors.map((c) => hexToRgba(c, 0.8)),
									borderWidth: 2,
									tension: 0.2,
								},
							],
						};
					} else {
						const color = getColorForMetric(v.slug);
						data[v.slug] = {
							labels: chart.data.map((x) =>
								'time' in x ? x.time || '' : x.label || '',
							),
							datasets: [
								{
									label: v.label,
									data: chart.data?.map((x) => x.value) ?? [],
									borderColor: color,
									backgroundColor: color,
									fill: false,
									tension: 0.2,
								},
							],
						};
					}
				}
			}
		}

		for (const v of window.__EVF_ANALYTICS__.data_sets.visualization
			.map((x) => x.metrics)
			.flat()) {
			if (!data[v.slug] && charts.some((x) => x.slug === v.slug)) {
				const chart = charts.find((x) => x.slug === v.slug);
				if (chart) {
					const color = getColorForMetric(v.slug);
					data[v.slug] = {
						labels: chart.data.map((x) =>
							'time' in x ? x.time || '' : x.label || '',
						),
						datasets: [
							{
								label: v.label,
								data: chart.data?.map((x) => x.value) ?? [],
								borderColor: color,
								backgroundColor: color,
								fill: false,
								tension: 0.3,
							},
						],
					};
				}
			}
		}

		return data;
	}, [charts]);

	const formatMoneyValue = (chart: any) => {
		if (!chart?.is_money) return undefined;
		const currencyCode = chart.currency || 'USD';

		return (value: number) => {
			const abs = Math.abs(value);
			let divisor = 1;
			let suffix = '';

			if (abs >= 1_000_000) {
				divisor = 1_000_000;
				suffix = 'M';
			} else if (abs >= 1_000) {
				divisor = 1_000;
				suffix = 'k';
			}

			const adjustedValue = value / divisor;

			try {
				const formatter = new Intl.NumberFormat('en-US', {
					style: 'currency',
					currency: currencyCode,
					minimumFractionDigits: suffix ? 1 : 0,
					maximumFractionDigits: suffix ? 1 : 0,
				});

				const formatted = formatter.format(adjustedValue);
				return suffix ? formatted + suffix : formatted;
			} catch (e) {
				const formatted = Math.round(adjustedValue).toString();
				return `$${formatted}${suffix}`;
			}
		};
	};

	const getChartMetadata = (visualizationSlug: string) => {
		const visualization = window.__EVF_ANALYTICS__.data_sets.visualization.find(
			(v) => v.slug === visualizationSlug,
		);
		if (visualization?.metrics?.length) {
			const firstMetricSlug = visualization.metrics[0].slug;
			const chart = charts.find((c) => c.slug === firstMetricSlug);
			return {
				isMoney: chart?.is_money || false,
				currency: chart?.currency || 'USD',
			};
		}
		const chart = charts.find((c) => c.slug === visualizationSlug);
		return {
			isMoney: chart?.is_money || false,
			currency: chart?.currency || 'USD',
		};
	};

	return (
		<div className="UR-Analytics-Content">
			<div className="UR-Analytics-Metrics">
				{summaryPreference.map((metric, index) => {
					const data = charts.find((c) => c.slug === metric) ?? {
						total: 0,
						previous_total: 0,
					};
					return (
						<MetricCard
							key={`${metric}-${index}`}
							selectedMetric={metric}
							value={data.total ?? 0}
							previousValue={data.previous_total ?? 0}
							onMetricChange={(v) => onMetricChange(index, v)}
							formatValue={formatMoneyValue(data)}
						/>
					);
				})}
			</div>
			<div className="UR-Analytics-Charts">
				{visualizationPreference.map((v, i) => {
					const chartData = chartDataGroups[v];
					if (!chartData) return null;

					const metadata = getChartMetadata(v);
					const isPieChart = getVisualizationChartType(v) === 'pie';
					const isList = getVisualizationChartType(v) === 'list';
					const visualization =
						window.__EVF_ANALYTICS__.data_sets.visualization.find(
							(vis) => vis.slug === v,
						);

					const isParentWithMultipleMetrics =
						visualization?.metrics && visualization.metrics.length > 1;

					let total: number | undefined;
					let previousTotal: number | undefined;
					let delta: number | undefined;
					let formatValueFunc: ((value: number) => string) | undefined;

					if (!isParentWithMultipleMetrics && !isPieChart && !isList) {
						const chart = charts.find((c) => c.slug === v);
						total = chart?.total;
						previousTotal = chart?.previous_total;
						delta = chart?.delta;
						formatValueFunc = isPieChart ? formatMoneyValue(chart) : undefined;
					}

					if (isList) {
						return (
							<ListCard
								key={v}
								initialMetric={v}
								height={300}
								metricOptions={getVisualizationOptions().filter((x) =>
									i === 0 ? !x.isPie && !x.isList : true,
								)}
								onMetricChange={(newMetric) =>
									onVisualizationChange(i, newMetric)
								}
								data={
									(charts.find((x) => x.slug === v)?.data ??
										[]) as unknown as Array<{
										referer_url: string;
										total_submissions: `${number}`;
										total_visits: `${number}`;
										unique_sessions: `${number}`;
									}>
								}
							/>
						);
					}

					return (
						<ChartCard
							key={v + '-' + i}
							isPrimary={i === 0}
							height={i === 0 ? 400 : 300}
							chartType={getVisualizationChartType(v) as 'line' | 'pie'}
							initialMetric={v}
							metricOptions={getVisualizationOptions().filter((x) =>
								i === 0 ? !x.isPie && !x.isList : true,
							)}
							onMetricChange={(newMetric) =>
								onVisualizationChange(i, newMetric)
							}
							data={chartData as ChartData<'pie' | 'line'>}
							isMoney={metadata.isMoney}
							currency={metadata.currency}
							total={total}
							previousTotal={previousTotal}
							delta={delta}
							formatValue={formatValueFunc}
						/>
					);
				})}
			</div>
		</div>
	);
};
