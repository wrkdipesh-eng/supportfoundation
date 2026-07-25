import {
	Box,
	Button,
	Icon,
	IconButton,
	Menu,
	MenuButton,
	MenuDivider,
	MenuGroup,
	MenuItem,
	MenuList,
	Portal,
	Text,
} from '@chakra-ui/react';
import * as React from 'react';
import { cn } from '../../lib/utils';
import { Check, ChevronDown, ChevronUp } from '../icons';

interface SelectContextValue {
	value?: string;
	onValueChange?: (value: string) => void;
}

const SelectContext = React.createContext<SelectContextValue>({});

interface SelectProps {
	value?: string;
	onValueChange?: (value: string) => void;
	defaultValue?: string;
	children: React.ReactNode;
}

function Select({ value, onValueChange, defaultValue, children }: SelectProps) {
	const [internalValue, setInternalValue] = React.useState(defaultValue);
	const currentValue = value ?? internalValue;

	const handleValueChange = (newValue: string) => {
		if (value === undefined) {
			setInternalValue(newValue);
		}
		onValueChange?.(newValue);
	};

	return (
		<SelectContext.Provider
			value={{ value: currentValue, onValueChange: handleValueChange }}
		>
			<Menu data-slot="select">{children}</Menu>
		</SelectContext.Provider>
	);
}

function SelectGroup({ children, ...props }: { children: React.ReactNode }) {
	return (
		<MenuGroup data-slot="select-group" {...props}>
			{children}
		</MenuGroup>
	);
}

interface SelectValueProps {
	placeholder?: string;
}

function SelectValue({ placeholder }: SelectValueProps) {
	const { value } = React.useContext(SelectContext);
	return <>{value || placeholder}</>;
}

interface SelectTriggerProps {
	className?: string;
	size?: 'sm' | 'default';
	children?: React.ReactNode;
	disabled?: boolean;
	dropdownIcon?: React.ReactNode;
	hideDropdownIcon?: boolean;
}

function SelectTrigger({
	className,
	size = 'default',
	children,
	disabled,
	dropdownIcon,
	hideDropdownIcon = false,
	...props
}: SelectTriggerProps) {
	const hasChildren = React.Children.count(children) > 0;

	const icon: any = hideDropdownIcon
		? undefined
		: (dropdownIcon ?? <Icon as={ChevronDown} fontSize={16} />);

	if (!hasChildren) {
		return (
			<MenuButton
				as={IconButton}
				aria-label="Open select"
				icon={icon}
				w="40px"
				minW="40px"
				data-slot="select-trigger"
				data-size={size}
				className={cn('EVF-UI-Select-Trigger', className)}
				isDisabled={disabled}
				{...props}
			/>
		);
	}

	return (
		<MenuButton
			as={Button}
			rightIcon={icon}
			data-slot="select-trigger"
			data-size={size}
			className={cn('EVF-UI-Select-Trigger', className)}
			isDisabled={disabled}
			{...props}
		>
			{children}
		</MenuButton>
	);
}

interface SelectContentProps {
	className?: string;
	children: React.ReactNode;
	position?: 'popper' | 'item-aligned';
	align?: 'start' | 'center' | 'end';
	[key: string]: any;
}

function SelectContent({
	className,
	children,
	position = 'popper',
	align = 'center',
	...props
}: SelectContentProps) {
	const scrollRef = React.useRef<HTMLDivElement>(null);
	const rafRef = React.useRef<number | null>(null);
	const [canScrollUp, setCanScrollUp] = React.useState(false);
	const [canScrollDown, setCanScrollDown] = React.useState(false);

	// Callback ref — fires when the element is actually attached to the DOM
	const viewportRef = React.useCallback((el: HTMLDivElement | null) => {
		(scrollRef as React.MutableRefObject<HTMLDivElement | null>).current = el;
		if (!el) return;

		const check = () => {
			setCanScrollUp(el.scrollTop > 1);
			setCanScrollDown(el.scrollTop + el.clientHeight < el.scrollHeight - 1);
		};

		// Defer so browser has finished layout before measuring
		setTimeout(check, 0);
		el.addEventListener('scroll', check);
		const ro = new ResizeObserver(check);
		ro.observe(el);
	}, []);

	const scrollDirRef = React.useRef<'up' | 'down' | null>(null);

	const startScroll = (dir: 'up' | 'down') => {
		stopScroll();
		const step = () => {
			const el = scrollRef.current;
			if (!el) return;
			el.scrollTop += dir === 'down' ? 4 : -4;
			rafRef.current = requestAnimationFrame(step);
		};
		rafRef.current = requestAnimationFrame(step);
	};

	const stopScroll = () => {
		if (rafRef.current !== null) {
			cancelAnimationFrame(rafRef.current);
			rafRef.current = null;
		}
	};

	const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
		const el = scrollRef.current;
		if (!el) return;
		const rect = e.currentTarget.getBoundingClientRect();
		const relY = e.clientY - rect.top;
		const h = rect.height;
		const canUp = el.scrollTop > 1;
		const canDown = el.scrollTop + el.clientHeight < el.scrollHeight - 1;
		const newDir: 'up' | 'down' | null =
			relY < 44 && canUp ? 'up' : relY > h - 44 && canDown ? 'down' : null;
		if (newDir !== scrollDirRef.current) {
			scrollDirRef.current = newDir;
			if (newDir) startScroll(newDir);
			else stopScroll();
		}
	};

	return (
		<Portal>
			<MenuList
				data-slot="select-content"
				className={cn(
					'EVF-UI-Select-Content',
					position === 'popper' && 'EVF-UI-Select-Content--popper',
					className,
				)}
				overflow="hidden"
				position="relative"
				{...props}
			>
				<Box
					ref={viewportRef}
					className={cn(
						'EVF-UI-Select-Viewport',
						position === 'popper' && 'EVF-UI-Select-Viewport--popper',
					)}
					overflowY="auto"
					maxH="420px"
					sx={{
						scrollbarWidth: 'none',
						'&::-webkit-scrollbar': { display: 'none' },
					}}
					onMouseMove={handleMouseMove}
					onMouseLeave={() => {
						scrollDirRef.current = null;
						stopScroll();
					}}
				>
					{children}
				</Box>
				{canScrollUp && (
					<Box
						data-slot="select-scroll-up-button"
						position="absolute"
						top={0}
						left={0}
						right={0}
						h="28px"
						zIndex={2}
						display="flex"
						alignItems="flex-start"
						justifyContent="center"
						pt="4px"
						cursor="default"
						userSelect="none"
						pointerEvents="none"
						background="#ffffff"
					>
						<ChevronUp size={18} />
					</Box>
				)}
				{canScrollDown && (
					<Box
						data-slot="select-scroll-down-button"
						position="absolute"
						bottom={0}
						left={0}
						right={0}
						h="28px"
						zIndex={2}
						display="flex"
						alignItems="flex-end"
						justifyContent="center"
						pb="4px"
						cursor="default"
						userSelect="none"
						pointerEvents="none"
						background="#ffffff"
					>
						<ChevronDown size={18} />
					</Box>
				)}
			</MenuList>
		</Portal>
	);
}

interface SelectLabelProps {
	className?: string;
	children: React.ReactNode;
}

function SelectLabel({ className, children, ...props }: SelectLabelProps) {
	return (
		<Text
			data-slot="select-label"
			className={cn('EVF-UI-Select-Label', className)}
			px={3}
			py={2}
			fontSize="sm"
			fontWeight="semibold"
			{...props}
		>
			{children}
		</Text>
	);
}

interface SelectItemProps {
	className?: string;
	children: React.ReactNode;
	value: string;
	disabled?: boolean;
}

function SelectItem({
	className,
	children,
	value,
	disabled,
	...props
}: SelectItemProps) {
	const { value: selectedValue, onValueChange } =
		React.useContext(SelectContext);
	const isSelected = selectedValue === value;

	return (
		<MenuItem
			data-slot="select-item"
			{...(disabled ? { 'data-disabled': '' } : {})}
			className={cn('EVF-UI-Select-Item', className)}
			onClick={() => onValueChange?.(value)}
			isDisabled={disabled}
			{...props}
		>
			<Box
				as="span"
				className="EVF-UI-Select-ItemIndicator"
				display="inline-flex"
				alignItems="center"
				mr={2}
			>
				{isSelected && <Check className="EVF-UI-Select-CheckIcon" size={16} />}
				{!isSelected && <Box w="16px" />}
			</Box>
			{children}
		</MenuItem>
	);
}

interface SelectSeparatorProps {
	className?: string;
}

function SelectSeparator({ className, ...props }: SelectSeparatorProps) {
	return (
		<MenuDivider
			data-slot="select-separator"
			className={cn('EVF-UI-Select-Separator', className)}
			{...props}
		/>
	);
}

function SelectScrollUpButton({ className }: { className?: string }) {
	return (
		<Box
			data-slot="select-scroll-up-button"
			className={cn('EVF-UI-Select-ScrollUpButton', className)}
			display="flex"
			alignItems="center"
			justifyContent="center"
			py={1}
		>
			<ChevronUp className="EVF-UI-Select-ChevronUpIcon" size={16} />
		</Box>
	);
}

function SelectScrollDownButton({ className }: { className?: string }) {
	return (
		<Box
			data-slot="select-scroll-down-button"
			className={cn('EVF-UI-Select-ScrollDownButton', className)}
			display="flex"
			alignItems="center"
			justifyContent="center"
			py={1}
		>
			<ChevronDown className="EVF-UI-Select-ChevronDownIcon" size={16} />
		</Box>
	);
}

export {
	Select,
	SelectContent,
	SelectGroup,
	SelectItem,
	SelectLabel,
	SelectScrollDownButton,
	SelectScrollUpButton,
	SelectSeparator,
	SelectTrigger,
	SelectValue,
};
