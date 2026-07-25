import {
	Button as ChakraButton,
	ButtonProps as ChakraButtonProps,
} from '@chakra-ui/react';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '../../lib/utils';

const buttonVariants = cva('', {
	variants: {
		variant: {
			default: 'EVF-UI-Button',
			destructive: 'EVF-UI-Button EVF-UI-Button--destructive',
			outline: 'EVF-UI-Button EVF-UI-Button--outline',
			secondary: 'EVF-UI-Button EVF-UI-Button--secondary',
			ghost: 'EVF-UI-Button EVF-UI-Button--ghost',
			link: 'EVF-UI-Button EVF-UI-Button--link',
		},
		size: {
			default: '',
			sm: 'EVF-UI-Button--sm',
			lg: 'EVF-UI-Button--lg',
			icon: 'EVF-UI-Button--icon',
			'icon-sm': 'EVF-UI-Button--icon-sm',
			'icon-lg': 'EVF-UI-Button--icon-lg',
		},
	},
	defaultVariants: {
		variant: 'default',
		size: 'default',
	},
});

type ButtonVariants = VariantProps<typeof buttonVariants>;

interface ButtonProps extends Omit<ChakraButtonProps, 'variant' | 'size'> {
	variant?: ButtonVariants['variant'];
	size?: ButtonVariants['size'];
	asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
	(
		{ className, variant, size, asChild = false, children, as, ...props },
		ref,
	) => {
		// If asChild is true and children is a valid element, clone it with props
		if (asChild && React.isValidElement(children)) {
			return React.cloneElement(children, {
				...children.props,
				...props,
				ref,
				className: cn(
					buttonVariants({ variant, size }),
					className,
					children.props.className,
				),
				'data-slot': 'button',
			} as any);
		}

		return (
			<ChakraButton
				ref={ref}
				data-slot="button"
				className={cn(buttonVariants({ variant, size }), className)}
				as={as}
				unstyled
				{...props}
			>
				{children}
			</ChakraButton>
		);
	},
);

Button.displayName = 'Button';

export { Button, buttonVariants };
