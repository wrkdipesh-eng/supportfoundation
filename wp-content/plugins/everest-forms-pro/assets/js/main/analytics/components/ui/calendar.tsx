import { Box } from '@chakra-ui/react';
import * as React from 'react';
import { DayButton, DayPicker, getDefaultClassNames } from 'react-day-picker';

import { cn } from '../../lib/utils';
import { ChevronDown, ChevronLeft, ChevronRight } from '../icons';
import { Button, buttonVariants } from './button';

export type CalendarProps = React.ComponentProps<typeof DayPicker> & {
	buttonVariant?: React.ComponentProps<typeof Button>['variant'];
};

function Calendar({
	className,
	classNames,
	showOutsideDays = true,
	captionLayout = 'label',
	buttonVariant = 'ghost',
	formatters,
	components,
	...props
}: CalendarProps): JSX.Element {
	const defaultClassNames = getDefaultClassNames();

	return (
		<DayPicker
			showOutsideDays={showOutsideDays}
			className={cn('EVF-UI-Calendar', className)}
			captionLayout={captionLayout}
			formatters={{
				formatMonthDropdown: (date) =>
					date.toLocaleString('default', { month: 'short' }),
				...formatters,
			}}
			classNames={{
				root: cn('EVF-UI-Calendar-Root', defaultClassNames.root),
				months: cn('EVF-UI-Calendar-Months', defaultClassNames.months),
				month: cn('EVF-UI-Calendar-Month', defaultClassNames.month),
				nav: cn('EVF-UI-Calendar-Nav', defaultClassNames.nav),
				button_previous: cn(
					buttonVariants({ variant: buttonVariant }),
					'EVF-UI-Calendar-NavButton',
					'EVF-UI-Calendar-NavButtonPrevious',
					defaultClassNames.button_previous,
				),
				button_next: cn(
					buttonVariants({ variant: buttonVariant }),
					'EVF-UI-Calendar-NavButton',
					'EVF-UI-Calendar-NavButtonNext',
					defaultClassNames.button_next,
				),
				month_caption: cn(
					'EVF-UI-Calendar-MonthCaption',
					defaultClassNames.month_caption,
				),
				dropdowns: cn('EVF-UI-Calendar-Dropdowns', defaultClassNames.dropdowns),
				dropdown_root: cn(
					'EVF-UI-Calendar-DropdownRoot',
					defaultClassNames.dropdown_root,
				),
				dropdown: cn('EVF-UI-Calendar-Dropdown', defaultClassNames.dropdown),
				caption_label: cn(
					'EVF-UI-Calendar-CaptionLabel',
					defaultClassNames.caption_label,
				),
				weekdays: cn('EVF-UI-Calendar-Weekdays', defaultClassNames.weekdays),
				weekday: cn('EVF-UI-Calendar-Weekday', defaultClassNames.weekday),
				week: cn('EVF-UI-Calendar-Week', defaultClassNames.week),
				week_number_header: cn(
					'EVF-UI-Calendar-WeekNumberHeader',
					defaultClassNames.week_number_header,
				),
				week_number: cn(
					'EVF-UI-Calendar-WeekNumber',
					defaultClassNames.week_number,
				),
				day: cn('EVF-UI-Calendar-Day', defaultClassNames.day),
				range_start: cn(
					'EVF-UI-Calendar-RangeStart',
					defaultClassNames.range_start,
				),
				range_middle: cn(
					'EVF-UI-Calendar-RangeMiddle',
					defaultClassNames.range_middle,
				),
				range_end: cn('EVF-UI-Calendar-RangeEnd', defaultClassNames.range_end),
				today: cn('EVF-UI-Calendar-Today', defaultClassNames.today),
				outside: cn('EVF-UI-Calendar-Outside', defaultClassNames.outside),
				disabled: cn('EVF-UI-Calendar-Disabled', defaultClassNames.disabled),
				hidden: cn('EVF-UI-Calendar-Hidden', defaultClassNames.hidden),
				...classNames,
			}}
			components={{
				Root: ({ className, rootRef, ...props }) => {
					return (
						<Box
							as="div"
							data-slot="calendar"
							ref={rootRef}
							className={cn(className)}
							{...props}
						/>
					);
				},
				Chevron: ({ className, orientation, ...props }) => {
					if (orientation === 'left') {
						return (
							<ChevronLeft
								className={cn('EVF-UI-Calendar-Chevron', className)}
								size={16}
								{...props}
							/>
						);
					}

					if (orientation === 'right') {
						return (
							<ChevronRight
								className={cn('EVF-UI-Calendar-Chevron', className)}
								size={16}
								{...props}
							/>
						);
					}

					return (
						<ChevronDown
							className={cn('EVF-UI-Calendar-Chevron', className)}
							size={16}
							{...props}
						/>
					);
				},
				DayButton: CalendarDayButton,
				WeekNumber: ({ children, ...props }) => {
					return (
						<td {...props}>
							<Box className="EVF-UI-Calendar-WeekNumberCell">{children}</Box>
						</td>
					);
				},
				...components,
			}}
			{...props}
		/>
	);
}

function CalendarDayButton({
	className,
	day,
	modifiers,
	...props
}: React.ComponentProps<typeof DayButton>): JSX.Element {
	const defaultClassNames = getDefaultClassNames();

	const ref = React.useRef<HTMLButtonElement>(null);
	React.useEffect(() => {
		if (modifiers.focused) ref.current?.focus();
	}, [modifiers.focused]);

	return (
		<Button
			ref={ref}
			variant="ghost"
			size="icon"
			data-day={day.date.toLocaleDateString()}
			data-selected-single={
				modifiers.selected &&
				!modifiers.range_start &&
				!modifiers.range_end &&
				!modifiers.range_middle
			}
			data-range-start={modifiers.range_start}
			data-range-end={modifiers.range_end}
			data-range-middle={modifiers.range_middle}
			data-day-same={modifiers.range_start && modifiers.range_end}
			className={cn(
				'EVF-UI-Calendar-DayButton',
				modifiers.selected && 'EVF-UI-Calendar-DayButton--selected-single',
				modifiers.range_start && 'EVF-UI-Calendar-DayButton--range-start',
				modifiers.range_end && 'EVF-UI-Calendar-DayButton--range-end',
				modifiers.range_middle && 'EVF-UI-Calendar-DayButton--range-middle',
				modifiers.range_start &&
					modifiers.range_end &&
					'EVF-UI-Calendar-DayButton--day-same',
				defaultClassNames.day,
				className,
			)}
			{...props}
		/>
	);
}

Calendar.displayName = 'Calendar';

export { Calendar, CalendarDayButton };
