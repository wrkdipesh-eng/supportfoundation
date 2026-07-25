import { useState } from 'react';
import { Line, Pie } from 'react-chartjs-2';
import {
	Chart as ChartJS,
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	BarElement,
	ArcElement,
	Title,
	Tooltip as ChartJSTooltip,
	Legend,
	Filler,
	ChartData,
	DefaultDataPoint,
	ChartOptions,
	Plugin,
} from 'chart.js';
import { cn } from '../lib/utils';
import {
	Select,
	SelectTrigger,
	SelectContent,
	SelectItem,
	SelectValue,
} from './ui/select';
import { __ } from '@wordpress/i18n';
import { MoveUp } from './icons/move-up';
import { MoveDown } from './icons/move-down';
import { useAnalyticsFilters } from '../hooks/use-analytics-filters';

const createLegendItem = (
	item: any,
	chart: any,
	className = 'UR-Analytics-Chart__LegendItem',
) => {
	const li = document.createElement('li');
	li.className = className;
	if (item.hidden) {
		li.classList.add('UR-Analytics-Chart__LegendItem--hidden');
	}

	const boxSpan = document.createElement('span');
	boxSpan.className = 'UR-Analytics-Chart__LegendColor';
	boxSpan.style.background = item.fillStyle as string;
	boxSpan.style.borderColor = item.strokeStyle as string;
	boxSpan.style.borderWidth = item.lineWidth + 'px';

	const textContainer = document.createElement('span');
	textContainer.className = 'UR-Analytics-Chart__LegendText';
	textContainer.textContent = item.text;

	li.appendChild(boxSpan);
	li.appendChild(textContainer);

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
		const containerID = options.containerID;
		if (!containerID) return;

		const container = document.getElementById(containerID);
		if (!container) return;

		while (container.firstChild) {
			container.firstChild.remove();
		}

		const items =
			chart.options.plugins?.legend?.labels?.generateLabels?.(chart);
		if (!items || items.length <= 1) return;

		const legendList = document.createElement('ul');
		legendList.className = 'UR-Analytics-Chart__LegendList';

		items.forEach((item) => {
			const legendItem = createLegendItem(item, chart);
			legendList.appendChild(legendItem);
		});

		container.appendChild(legendList);
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

const formatChartValue = (
	value: number,
	type: 'count' | 'currency',
	currencyCode: string = 'USD',
) => {
	const abs = Math.abs(value);

	let formatted: string;
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

	if (type === 'currency') {
		try {
			const formatter = new Intl.NumberFormat('en-US', {
				style: 'currency',
				currency: currencyCode,
				minimumFractionDigits: suffix ? 1 : 0,
				maximumFractionDigits: suffix ? 1 : 0,
			});
			const currencyFormatted = formatter.format(adjustedValue);
			return suffix ? currencyFormatted + suffix : currencyFormatted;
		} catch (e) {
			formatted = Math.round(adjustedValue).toString();
			return `$${formatted}${suffix}`;
		}
	}

	formatted = Math.round(adjustedValue).toString();
	return suffix ? `${formatted}${suffix}` : formatted;
};

const getNiceStepSize = (max: number) => {
	if (max <= 100) return 10;
	if (max <= 500) return 50;
	if (max <= 1_000) return 100;
	if (max <= 5_000) return 500;
	if (max <= 10_000) return 1_000;
	if (max <= 50_000) return 5_000;
	if (max <= 100_000) return 10_000;
	if (max <= 500_000) return 50_000;
	if (max <= 1_000_000) return 100_000;
	return Math.pow(10, Math.floor(Math.log10(max)));
};

const getMaxValue = (datasets: ChartData['datasets']) => {
	return Math.max(
		...datasets.flatMap((ds) =>
			(ds.data as number[]).filter((v) => typeof v === 'number'),
		),
	);
};

export const ChartCard = <
	TType extends 'line' | 'pie',
	TData extends DefaultDataPoint<TType>,
	TLabel,
>({
	chartType,
	data,
	initialMetric,
	height,
	metricOptions,
	onMetricChange,
	className,
	isMoney,
	currency,
	total,
	previousTotal,
	delta,
	formatValue,
	isPrimary,
}: {
	chartType: TType;
	data: ChartData<TType, TData, TLabel>;
	initialMetric: string;
	onMetricChange: (v: string) => void;
	height?: number;
	metricOptions: Array<{
		label: string;
		value: string;
		indent?: boolean;
	}>;
	className?: string;
	isMoney?: boolean;
	currency?: string;
	total?: number;
	previousTotal?: number;
	delta?: number;
	formatValue?: (value: number) => string;
	isPrimary?: boolean;
}) => {
	const [metric, setMetric] = useState<string>(initialMetric);
	const maxValue = getMaxValue(data.datasets as ChartData['datasets']);
	const stepSize = getNiceStepSize(maxValue);
	const currencyCode = currency || 'USD';
	const valueType = isMoney ? 'currency' : 'count';
	const shouldShowComparison =
		total !== undefined || previousTotal !== undefined;
	const { filters } = useAnalyticsFilters();

	const calculatePercentageChange = (): number | null => {
		if (!shouldShowComparison) return null;

		if (delta !== undefined && delta !== null) {
			return delta;
		}
		if (
			total !== undefined &&
			previousTotal !== undefined &&
			previousTotal !== null
		) {
			if (previousTotal === 0) {
				return total > 0 ? 100 : 0;
			}
			return ((total - previousTotal) / previousTotal) * 100;
		}
		return null;
	};

	const percentageChange = calculatePercentageChange();
	const isPositive = percentageChange !== null && percentageChange > 0;
	const isNegative = percentageChange !== null && percentageChange < 0;

	const chartOptions = {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			HTMLLegend: {
				containerID: `UR-Analytics-Legend-Container-${metric}`,
			},
			legend: {
				// display: useHTMLLegend ? false : shouldShowLegend,
				// position: 'top' as const,
				// align: chartType === 'pie' ? ('center' as const) : ('start' as const),
				// labels: {
				// 	usePointStyle: true,
				// 	padding: 20,
				// 	font: {
				// 		size: 12,
				// 		family:
				// 			'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
				// 	},
				// },
				display: false,
			},
			tooltip: {
				intersect: false,
				enabled: true,
				backgroundColor: 'rgba(0, 0, 0, 0.8)',
				padding: 12,
				titleFont: {
					size: 13,
					weight: 'bold' as const,
				},
				bodyFont: {
					size: 12,
				},
				cornerRadius: 4,
				displayColors: true,
				callbacks: {
					label: (context: any) => {
						const value =
							context.parsed.y !== null && context.parsed.y !== undefined
								? context.parsed.y
								: context.parsed;
						if (isMoney) {
							return `${context.dataset.label}: ${formatChartValue(
								value,
								'currency',
								currencyCode,
							)}`;
						}
						return `${context.dataset.label}: ${formatChartValue(
							value,
							'count',
						)}`;
					},
				},
			},
		},
		elements: {
			point: {
				radius: isPrimary ? 4 : 0,
				hoverRadius: 6,
				hitRadius: 14,
			},
		},
		interaction: {
			mode: 'index',
			intersect: false,
		},
		scales:
			chartType === 'line'
				? {
						x: {
							grid: {
								display: false,
							},
							// ticks: {
							// 	font: {
							// 		size: 11,
							// 	},
							// 	color: '#6b7280',
							// },
							border: {
								display: true,
							},
							stacked: false,
							ticks: {
								autoSkip: false,
								callback: function (
									this: any,
									value: string | number,
									index: number,
									ticks: any[],
								): string {
									const isFirst = index === 0;
									const isLast = index === ticks.length - 1;
									let label = this.getLabelForValue(value as number);

									if (filters.unit === 'month') {
										label = new Date(label).toLocaleString('en-US', {
											month: 'short',
										});
									}

									if (isPrimary) {
										return label;
									}
									return isFirst || isLast ? label : '';
								},
								maxRotation: isPrimary ? undefined : 0,
								minRotation: isPrimary ? undefined : 0,
							},
						},
						y: {
							min: 0,
							grid: {
								display: false,
							},
							beginAtZero: true,
							suggestedMax: Math.ceil(maxValue / stepSize) * stepSize,
							ticks: {
								font: {
									size: 11,
								},
								color: '#6b7280',
								precision: 0,
								callback: (value: number) => {
									return formatChartValue(value, valueType, currencyCode);
								},
							},
							border: {
								display: true,
							},
						},
					}
				: undefined,
	} as unknown as ChartOptions<TType>;

	const formatDisplayValue = (value: number | undefined): string => {
		if (value === undefined || value === null) return '0';
		if (formatValue) {
			return formatValue(value);
		}
		if (isMoney) {
			return formatChartValue(value, 'currency', currencyCode);
		}
		return formatChartValue(value, 'count');
	};

	return (
		<div
			className={cn(
				'UR-Analytics-Chart',
				(data.datasets.length > 1 || chartType === 'pie') &&
					'UR-Analytics-Chart--Multi',
				isPrimary && 'UR-Analytics-Chart--Primary',
				className,
			)}
		>
			<div className="UR-Analytics-Chart__Header">
				<div className="UR-Analytics-Chart__ParentSelector">
					<Select
						defaultValue={metric}
						onValueChange={(v) => {
							setMetric(v);
							onMetricChange(v);
						}}
					>
						<SelectTrigger className="UR-Analytics-Chart__Selector">
							<SelectValue />
						</SelectTrigger>
						<SelectContent
							align="start"
							collisionPadding={32}
							className="UR-Analytics-Chart__SelectContent"
						>
							{metricOptions.map((v) => (
								<SelectItem
									key={v.value}
									value={v.value}
									className={cn(
										'UR-Analytics-Chart__SelectItem',
										v.indent && 'UR-Analytics-Chart__SelectItem--indented',
									)}
								>
									{v.label}
								</SelectItem>
							))}
						</SelectContent>
					</Select>
				</div>
				{((isPrimary && data.datasets.length > 1) || chartType === 'pie') && (
					<div
						id={`UR-Analytics-Legend-Container-${metric}`}
						className={cn(
							'UR-Analytics-Chart__HTMLLegend',
							isPrimary && 'UR-Analytics-Chart__HTMLLegend--Primary',
						)}
					></div>
				)}
				{shouldShowComparison && (
					<div className="UR-Analytics-Chart__Comparison">
						<div className="UR-Analytics-Chart__Total">
							{formatDisplayValue(total)}
							{percentageChange !== null && (
								<span
									className={cn(
										'UR-Analytics-Chart__Delta',
										isPositive && 'UR-Analytics-Chart__Delta--positive',
										isNegative && 'UR-Analytics-Chart__Delta--negative',
									)}
								>
									{isPositive && (
										<MoveUp
											size="16"
											className="UR-Analytics-Chart__DeltaIcon"
										/>
									)}
									{isNegative && (
										<MoveDown
											size="16"
											className="UR-Analytics-Chart__DeltaIcon"
										/>
									)}
									{Math.abs(percentageChange).toFixed(1)}%
								</span>
							)}
						</div>
						{previousTotal !== undefined && (
							<div className="UR-Analytics-Chart__PreviousComparison">
								{__('vs.', 'user-registration')}{' '}
								{formatDisplayValue(previousTotal)}{' '}
								{__('last period', 'user-registration')}
							</div>
						)}
					</div>
				)}
			</div>
			<div
				className="UR-Analytics-Chart__Content"
				style={{ minHeight: `${height}px` }}
			>
				<div className="UR-Analytics-Chart__ChartContainer">
					{chartType === 'pie' ? (
						<Pie
							height={height}
							data={data as ChartData<'pie', TData, TLabel>}
							options={chartOptions as ChartOptions<'pie'>}
						/>
					) : (
						<Line
							height={height}
							data={data as ChartData<'line', TData, TLabel>}
							options={chartOptions as ChartOptions<'line'>}
						/>
					)}
				</div>
			</div>
		</div>
	);
};
