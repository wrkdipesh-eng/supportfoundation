import { __ } from '@wordpress/i18n';
import { cn } from '../lib/utils';
import { MoveDown } from './icons/move-down';
import { MoveUp } from './icons/move-up';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from './ui/select';

export interface StatCardProps {
	value: number;
	previousValue?: number;
	comparisonPercentage?: number;
	action?: {
		label: string;
		onClick?: () => void;
		href?: string;
	};
	onMetricChange: (metric: string) => void;
	selectedMetric: string;
	className?: string;
	formatValue?: (value: number) => string;
}

export function MetricCard({
	value,
	previousValue,
	comparisonPercentage,
	action,
	onMetricChange,
	selectedMetric,
	className,
	formatValue,
}: StatCardProps) {
	const formattedValue = formatValue
		? formatValue(value)
		: new Intl.NumberFormat().format(value);

	const handleMetricChange = (newMetric: string) => {
		if (onMetricChange) {
			onMetricChange(newMetric);
		}
	};

	const calculatePercentageChange = (): number | null => {
		if (comparisonPercentage !== undefined) {
			return comparisonPercentage;
		}
		if (previousValue !== undefined && previousValue !== null) {
			if (previousValue === 0) {
				return value > 0 ? 100 : 0;
			}
			return ((value - previousValue) / previousValue) * 100;
		}
		return null;
	};

	const percentageChange = calculatePercentageChange();
	const isRefundedRevenue = selectedMetric === 'refunded-revenue';
	const rawIsPositive = percentageChange !== null && percentageChange > 0;
	const rawIsNegative = percentageChange !== null && percentageChange < 0;

	const isPositive = isRefundedRevenue ? rawIsNegative : rawIsPositive;
	const isNegative = isRefundedRevenue ? rawIsPositive : rawIsNegative;

	return (
		<div className={cn('UR-Analytics-Metric', className)}>
			<div className="UR-Analytics-Metric__Header">
				<Select
					defaultValue={selectedMetric}
					onValueChange={handleMetricChange}
				>
					<SelectTrigger className="UR-Analytics-Metric__MetricSelector">
						<SelectValue />
					</SelectTrigger>
					<SelectContent
						align="start"
						collisionPadding={32}
						className="UR-UI-Select-Content"
					>
						{window.__EVF_ANALYTICS__.data_sets.summary.map((s) => (
							<SelectItem key={s.slug} value={s.slug}>
								{s.label}
							</SelectItem>
						))}
					</SelectContent>
				</Select>
			</div>

			<div className="UR-Analytics-Metric__Content">
				<div className="UR-Analytics-Metric__Value">
					{formattedValue}
					{percentageChange !== null && (
						<span
							className={cn(
								'UR-Analytics-Metric__Delta',
								isPositive && 'UR-Analytics-Metric__Delta--positive',
								isNegative && 'UR-Analytics-Metric__Delta--negative',
							)}
						>
							{isPositive && (
								<MoveUp size="16" className="UR-Analytics-Metric__DeltaIcon" />
							)}
							{isNegative && (
								<MoveDown
									size="16"
									className="UR-Analytics-Metric__DeltaIcon"
								/>
							)}
							{Math.abs(percentageChange).toFixed(1)}%
						</span>
					)}{' '}
				</div>

				{previousValue !== undefined && (
					<div className="UR-Analytics-Metric__Comparison">
						{__('vs.', 'user-registration')}{' '}
						{formatValue
							? formatValue(previousValue)
							: new Intl.NumberFormat().format(previousValue)}{' '}
						{__('last period', 'user-registration')}
					</div>
				)}
			</div>

			{action && (
				<div className="UR-Analytics-Metric__Action">
					{action.href ? (
						<a
							href={action.href}
							className="UR-Analytics-Metric__ActionLink"
							onClick={action.onClick}
						>
							{action.label}
						</a>
					) : (
						<button
							type="button"
							className="UR-Analytics-Metric__ActionButton"
							onClick={action.onClick}
						>
							{action.label}
						</button>
					)}
				</div>
			)}
		</div>
	);
}
