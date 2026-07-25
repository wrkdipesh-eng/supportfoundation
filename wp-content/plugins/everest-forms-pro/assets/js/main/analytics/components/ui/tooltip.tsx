import { Box, Tooltip as ChakraTooltip, Portal } from '@chakra-ui/react';
import * as React from 'react';

import { cn } from '../../lib/utils';

interface TooltipContextValue {
	isOpen?: boolean;
	onOpenChange?: (open: boolean) => void;
	delayDuration?: number;
}

const TooltipContext = React.createContext<TooltipContextValue>({});

interface TooltipProviderProps {
	delayDuration?: number;
	children: React.ReactNode;
}

function TooltipProvider({
	delayDuration = 0,
	children,
	...props
}: TooltipProviderProps) {
	return (
		<Box data-slot="tooltip-provider" {...props}>
			{children}
		</Box>
	);
}

interface TooltipProps {
	open?: boolean;
	defaultOpen?: boolean;
	onOpenChange?: (open: boolean) => void;
	delayDuration?: number;
	children: React.ReactNode;
}

function Tooltip({
	open,
	defaultOpen,
	onOpenChange,
	delayDuration = 0,
	children,
	...props
}: TooltipProps) {
	const [isOpen, setIsOpen] = React.useState(defaultOpen ?? false);
	const currentIsOpen = open ?? isOpen;

	const handleOpenChange = (newOpen: boolean) => {
		if (open === undefined) {
			setIsOpen(newOpen);
		}
		onOpenChange?.(newOpen);
	};

	return (
		<TooltipContext.Provider
			value={{
				isOpen: currentIsOpen,
				onOpenChange: handleOpenChange,
				delayDuration,
			}}
		>
			<TooltipProvider delayDuration={delayDuration}>
				<Box data-slot="tooltip" {...props}>
					{children}
				</Box>
			</TooltipProvider>
		</TooltipContext.Provider>
	);
}

interface TooltipTriggerProps {
	children: React.ReactNode;
	asChild?: boolean;
}

function TooltipTrigger({ children, asChild, ...props }: TooltipTriggerProps) {
	return (
		<Box data-slot="tooltip-trigger" {...props}>
			{children}
		</Box>
	);
}

interface TooltipContentProps {
	className?: string;
	sideOffset?: number;
	side?: 'top' | 'right' | 'bottom' | 'left';
	align?: 'start' | 'center' | 'end';
	children: React.ReactNode;
}

function TooltipContent({
	className,
	sideOffset = 0,
	side = 'top',
	align = 'center',
	children,
	...props
}: TooltipContentProps) {
	const { isOpen, delayDuration } = React.useContext(TooltipContext);

	// Map side and align to Chakra placement
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
			<Box
				data-slot="tooltip-content"
				className={cn('EVF-UI-Tooltip-Content', className)}
				role="tooltip"
				{...props}
			>
				{children}
				<Box className="EVF-UI-Tooltip-Arrow" />
			</Box>
		</Portal>
	);
}

// Wrapper component that combines trigger and content for easier use
interface TooltipWrapperProps {
	label: string | React.ReactNode;
	placement?:
		| 'top'
		| 'right'
		| 'bottom'
		| 'left'
		| 'top-start'
		| 'top-end'
		| 'bottom-start'
		| 'bottom-end';
	openDelay?: number;
	hasArrow?: boolean;
	className?: string;
	children: React.ReactNode;
}

function TooltipWrapper({
	label,
	placement = 'top',
	openDelay = 0,
	hasArrow = true,
	className,
	children,
}: TooltipWrapperProps) {
	return (
		<ChakraTooltip
			label={label}
			placement={placement}
			openDelay={openDelay}
			hasArrow={hasArrow}
			className={cn('EVF-UI-Tooltip-Content', className)}
		>
			{children}
		</ChakraTooltip>
	);
}

export {
	Tooltip,
	TooltipContent,
	TooltipProvider,
	TooltipTrigger,
	TooltipWrapper,
};
