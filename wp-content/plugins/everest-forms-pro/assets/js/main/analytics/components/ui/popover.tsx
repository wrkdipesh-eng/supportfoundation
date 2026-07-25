import {
	Popover as ChakraPopover,
	PopoverAnchor as ChakraPopoverAnchor,
	PopoverContent as ChakraPopoverContent,
	PopoverTrigger as ChakraPopoverTrigger,
	Portal,
} from '@chakra-ui/react';
import * as React from 'react';
import { cn } from '../../lib/utils';

interface PopoverProps {
	open?: boolean;
	defaultOpen?: boolean;
	onOpenChange?: (open: boolean) => void;
	children: React.ReactNode;
	placement?: string;
}

function Popover({
	open,
	defaultOpen,
	onOpenChange,
	children,
	placement = 'bottom',
	...props
}: PopoverProps) {
	return (
		<ChakraPopover
			data-slot="popover"
			isOpen={open}
			defaultIsOpen={defaultOpen}
			onOpen={() => onOpenChange?.(true)}
			onClose={() => onOpenChange?.(false)}
			placement={placement as any}
			{...props}
		>
			{children}
		</ChakraPopover>
	);
}

interface PopoverTriggerProps {
	className?: string;
	children: React.ReactNode;
	asChild?: boolean;
}

function PopoverTrigger({
	className,
	asChild,
	children,
	...props
}: PopoverTriggerProps) {
	return (
		<ChakraPopoverTrigger
			data-slot="popover-trigger"
			className={cn('EVF-UI-Popover-Trigger', className)}
			{...props}
		>
			{children}
		</ChakraPopoverTrigger>
	);
}

interface PopoverContentProps {
	className?: string;
	align?: 'start' | 'center' | 'end';
	side?: 'top' | 'right' | 'bottom' | 'left';
	sideOffset?: number;
	children: React.ReactNode;
}

function PopoverContent({
	className,
	align = 'start',
	side = 'bottom',
	sideOffset = 4,
	children,
	...props
}: PopoverContentProps) {
	// Map Radix align to Chakra placement
	const getPlacement = () => {
		const alignMap = {
			start: 'start',
			center: '',
			end: 'end',
		};
		const alignSuffix = align === 'center' ? '' : `-${alignMap[align]}`;
		return `${side}${alignSuffix}` as any;
	};

	return (
		<Portal>
			<ChakraPopoverContent
				data-slot="popover-content"
				className={cn('EVF-UI-Popover', className)}
				placement={getPlacement()}
				offset={[0, sideOffset]}
				{...props}
			>
				{children}
			</ChakraPopoverContent>
		</Portal>
	);
}

interface PopoverAnchorProps {
	className?: string;
	children: React.ReactNode;
}

function PopoverAnchor({ className, children, ...props }: PopoverAnchorProps) {
	return (
		<ChakraPopoverAnchor
			data-slot="popover-anchor"
			className={cn('EVF-UI-Popover-Anchor', className)}
			{...props}
		>
			{children}
		</ChakraPopoverAnchor>
	);
}

export { Popover, PopoverAnchor, PopoverContent, PopoverTrigger };
