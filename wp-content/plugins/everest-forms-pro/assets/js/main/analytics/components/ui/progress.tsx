import { Box, Progress as ChakraProgress } from '@chakra-ui/react';

import { cn } from '../../lib/utils';

interface ProgressProps {
	className?: string;
	value?: number;
	max?: number;
}

function Progress({
	className,
	value = 0,
	max = 100,
	...props
}: ProgressProps) {
	return (
		<Box
			data-slot="progress"
			className={cn('EVF-UI-Progress-Root', className)}
			position="relative"
			{...props}
		>
			<ChakraProgress
				value={value}
				max={max}
				className="EVF-UI-Progress-Indicator"
				data-slot="progress-indicator"
				sx={{
					'& > div': {
						transition: 'width 0.3s ease',
					},
				}}
			/>
		</Box>
	);
}

export { Progress };
