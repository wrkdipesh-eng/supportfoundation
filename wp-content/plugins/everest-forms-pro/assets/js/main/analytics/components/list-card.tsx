import { useState } from 'react';
import { cn } from '../lib/utils';
import {
	Select,
	SelectTrigger,
	SelectContent,
	SelectItem,
	SelectValue,
} from './ui/select';
import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink } from './icons/external-link';

export const ListCard = <
	TData extends Array<{
		referer_url: string;
		total_submissions: `${number}`;
		total_visits: `${number}`;
		unique_sessions: `${number}`;
	}>,
>({
	data,
	initialMetric,
	height,
	metricOptions,
	onMetricChange,
	className,
}: {
	data: TData;
	initialMetric: string;
	onMetricChange: (v: string) => void;
	height?: number;
	metricOptions: Array<{
		label: string;
		value: string;
		indent?: boolean;
	}>;
	className?: string;
}) => {
	const [metric, setMetric] = useState<string>(initialMetric);

	console.log(data);

	return (
		<div className={cn('UR-Analytics-Chart', className)}>
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
			</div>
			<div
				className="UR-Analytics-Chart__Content"
				style={{ height: `${height}px` }}
			>
				<div className="UR-Analytics-Chart__ChartContainer">
					<ol>
						{data.map((x) => (
							<li key={x.referer_url}>
								<span>{x.referer_url}</span>
								<span>{`(${x.total_visits})`}</span>
								<a href={x.referer_url} target="_blank">
									<span className="screen-reader-text">
										{sprintf(
											__('Opens %s in a new tab', 'user-registration'),
											x.referer_url,
										)}
									</span>
									<ExternalLink />
								</a>
							</li>
						))}
					</ol>
				</div>
			</div>
		</div>
	);
};
