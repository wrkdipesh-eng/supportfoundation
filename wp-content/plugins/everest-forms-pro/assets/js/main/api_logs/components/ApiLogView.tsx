import {
	Badge,
	Box,
	Button,
	Flex,
	Grid,
	Icon,
	Skeleton,
	Stack,
	Tab,
	TabList,
	TabPanel,
	TabPanels,
	Tabs,
	Text,
	Tooltip,
	useClipboard,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	FiAlertCircle,
	FiAlertTriangle,
	FiCalendar,
	FiCheck,
	FiCheckCircle,
	FiCopy,
	FiFileText,
	FiInfo,
	FiSend,
	FiServer,
	FiXCircle,
} from 'react-icons/fi';

const getStatusMeta = (status: string | number) => {
	const code = Number(status);
	if (code >= 200 && code < 300)
		return {
			scheme: 'green',
			label: __('Success', 'everest-forms-pro'),
			icon: FiCheckCircle,
			heroBg: 'green.50',
			heroBorder: 'green.200',
			heroText: 'green.700',
			iconColor: 'green.500',
		};
	if (code >= 300 && code < 400)
		return {
			scheme: 'orange',
			label: __('Redirect', 'everest-forms-pro'),
			icon: FiAlertTriangle,
			heroBg: 'orange.50',
			heroBorder: 'orange.200',
			heroText: 'orange.700',
			iconColor: 'orange.500',
		};
	if (code >= 400 && code < 500)
		return {
			scheme: 'red',
			label: __('Client Error', 'everest-forms-pro'),
			icon: FiXCircle,
			heroBg: 'red.50',
			heroBorder: 'red.200',
			heroText: 'red.700',
			iconColor: 'red.500',
		};
	if (code >= 500)
		return {
			scheme: 'red',
			label: __('Server Error', 'everest-forms-pro'),
			icon: FiAlertCircle,
			heroBg: 'red.50',
			heroBorder: 'red.200',
			heroText: 'red.700',
			iconColor: 'red.500',
		};
	return {
		scheme: 'gray',
		label: __('Unknown', 'everest-forms-pro'),
		icon: FiInfo,
		heroBg: 'gray.50',
		heroBorder: 'gray.200',
		heroText: 'gray.700',
		iconColor: 'gray.400',
	};
};

const tryPrettyJson = (value: string) => {
	if (!value) return '—';
	try {
		return JSON.stringify(JSON.parse(value), null, 2);
	} catch {
		return value;
	}
};

const MetaCard = ({
	icon,
	label,
	value,
}: {
	icon: React.ElementType;
	label: string;
	value: string;
}) => (
	<Box
		bg="white"
		border="1px solid"
		borderColor="gray.200"
		borderRadius="10px"
		p="14px 16px"
		_hover={{ borderColor: 'gray.300', shadow: 'sm' }}
		transition="all 0.15s"
	>
		<Flex align="center" gap="6px" mb="7px">
			<Icon as={icon} color="gray.400" boxSize="12px" />
			<Text
				fontWeight="600"
				fontSize="10px"
				textTransform="uppercase"
				letterSpacing="0.09em"
				color="gray.400"
			>
				{label}
			</Text>
		</Flex>
		<Text
			fontSize="14px"
			color="gray.800"
			fontWeight="500"
			overflow="hidden"
			textOverflow="ellipsis"
			whiteSpace="nowrap"
		>
			{value || '—'}
		</Text>
	</Box>
);

const CopyButton = ({ value }: { value: string }) => {
	const { hasCopied, onCopy } = useClipboard(value || '');
	return (
		<Tooltip
			label={
				hasCopied
					? __('Copied!', 'everest-forms-pro')
					: __('Copy to clipboard', 'everest-forms-pro')
			}
			placement="top"
			hasArrow
		>
			<Button
				size="xs"
				onClick={onCopy}
				leftIcon={hasCopied ? <FiCheck /> : <FiCopy />}
				variant="ghost"
				colorScheme={hasCopied ? 'green' : 'gray'}
				color={hasCopied ? 'green.300' : 'gray.500'}
				_hover={{
					color: hasCopied ? 'green.300' : 'white',
					bg: 'whiteAlpha.200',
				}}
				fontSize="11px"
				px="8px"
				h="26px"
			>
				{hasCopied
					? __('Copied', 'everest-forms-pro')
					: __('Copy', 'everest-forms-pro')}
			</Button>
		</Tooltip>
	);
};

const CodeBlock = ({
	children,
	value,
	label,
}: {
	children: React.ReactNode;
	value: string;
	label: string;
}) => (
	<Box borderRadius="0 0 8px 8px" overflow="hidden">
		<Flex
			align="center"
			justify="space-between"
			bg="gray.800"
			px="14px"
			py="8px"
			borderBottom="1px solid"
			borderColor="gray.700"
		>
			<Flex align="center" gap="6px">
				<Box w="10px" h="10px" borderRadius="full" bg="red.400" />
				<Box w="10px" h="10px" borderRadius="full" bg="yellow.400" />
				<Box w="10px" h="10px" borderRadius="full" bg="green.400" />
				<Text
					fontSize="11px"
					color="gray.400"
					fontFamily="'Courier New', Courier, monospace"
					ml="8px"
				>
					{label}
				</Text>
			</Flex>
			<CopyButton value={value} />
		</Flex>
		<Box
			as="pre"
			bg="gray.900"
			color="green.300"
			fontSize="12px"
			fontFamily="'Courier New', Courier, monospace"
			p="16px"
			overflow="auto"
			maxH="260px"
			whiteSpace="pre-wrap"
			wordBreak="break-all"
			lineHeight="1.7"
		>
			{children}
		</Box>
	</Box>
);

const LoadingSkeleton = () => (
	<Stack gap="20px">
		<Box
			bg="gray.50"
			border="1px solid"
			borderColor="gray.100"
			borderRadius="12px"
			p="20px"
		>
			<Flex gap="14px" align="center">
				<Skeleton h="32px" w="32px" borderRadius="full" />
				<Stack gap="8px">
					<Flex gap="10px" align="center">
						<Skeleton h="28px" w="72px" borderRadius="6px" />
						<Skeleton h="16px" w="90px" />
					</Flex>
					<Skeleton h="11px" w="110px" />
				</Stack>
			</Flex>
		</Box>
		<Grid templateColumns="repeat(3, 1fr)" gap="12px">
			{[1, 2, 3].map((i) => (
				<Box
					key={i}
					border="1px solid"
					borderColor="gray.100"
					borderRadius="10px"
					p="14px 16px"
				>
					<Skeleton h="10px" w="55px" mb="10px" />
					<Skeleton h="16px" w="90px" />
				</Box>
			))}
		</Grid>
		<Box border="1px solid" borderColor="gray.100" borderRadius="8px" p="16px">
			<Skeleton h="10px" w="80px" mb="10px" />
			<Skeleton h="16px" w="100%" mb="6px" />
			<Skeleton h="16px" w="70%" />
		</Box>
		<Box>
			<Skeleton h="36px" borderRadius="8px 8px 0 0" />
			<Skeleton h="180px" borderRadius="0 0 8px 8px" />
		</Box>
	</Stack>
);

const ApiLogView: React.FC = ({ singleLogData, isLoading }: any) => {
	if (isLoading || !singleLogData || Object.keys(singleLogData).length === 0) {
		return <LoadingSkeleton />;
	}

	const { created_date, form_name, log, request, response, source, status } =
		singleLogData;
	const {
		scheme,
		label,
		icon: StatusIcon,
		heroBg,
		heroBorder,
		heroText,
		iconColor,
	} = getStatusMeta(status);

	const prettyRequest = tryPrettyJson(request);
	const prettyResponse = tryPrettyJson(response);

	return (
		<Stack gap="20px">
			<Box
				bg={heroBg}
				border="1px solid"
				borderColor={heroBorder}
				borderRadius="12px"
				p="18px 22px"
			>
				<Flex align="center" gap="14px">
					<Icon
						as={StatusIcon}
						color={iconColor}
						boxSize="30px"
						flexShrink={0}
					/>
					<Box>
						<Flex align="center" gap="10px" mb="3px">
							<Badge
								colorScheme={scheme}
								fontSize="15px"
								fontWeight="700"
								px="12px"
								py="4px"
								borderRadius="6px"
								letterSpacing="0.04em"
							>
								{status}
							</Badge>
							<Text fontSize="15px" fontWeight="600" color={heroText}>
								{label}
							</Text>
						</Flex>
						<Text fontSize="11px" color="gray.500" letterSpacing="0.02em">
							{__('HTTP Status Code', 'everest-forms-pro')}
						</Text>
					</Box>
				</Flex>
			</Box>

			<Grid templateColumns="repeat(3, 1fr)" gap="12px">
				<MetaCard
					icon={FiFileText}
					label={__('Form Name', 'everest-forms-pro')}
					value={form_name}
				/>
				<MetaCard
					icon={FiServer}
					label={__('Source', 'everest-forms-pro')}
					value={source}
				/>
				<MetaCard
					icon={FiCalendar}
					label={__('Date', 'everest-forms-pro')}
					value={created_date}
				/>
			</Grid>

			<Box
				bg="gray.50"
				border="1px solid"
				borderColor="gray.200"
				borderRadius="10px"
				p="16px"
			>
				<Flex align="center" gap="6px" mb="8px">
					<Icon as={FiInfo} color="primary.400" boxSize="13px" />
					<Text
						fontWeight="700"
						fontSize="10px"
						textTransform="uppercase"
						letterSpacing="0.09em"
						color="gray.500"
					>
						{__('Log Message', 'everest-forms-pro')}
					</Text>
				</Flex>
				<Text
					fontSize="13.5px"
					color="gray.700"
					lineHeight="1.75"
					fontFamily="inherit"
				>
					{log || '—'}
				</Text>
			</Box>

			<Tabs variant="line" colorScheme="blue" size="sm" isLazy>
				<Box
					border="1px solid"
					borderColor="gray.200"
					borderRadius="10px"
					overflow="hidden"
				>
					<TabList
						bg="gray.50"
						borderBottom="1px solid"
						borderColor="gray.200"
						px="8px"
						pt="6px"
					>
						<Tab
							fontWeight="600"
							fontSize="12.5px"
							color="gray.500"
							pb="8px"
							_selected={{
								color: 'primary.600',
								borderColor: 'primary.500',
							}}
						>
							<Icon as={FiSend} mr="6px" boxSize="12px" />
							{__('Request', 'everest-forms-pro')}
						</Tab>
						<Tab
							fontWeight="600"
							fontSize="12.5px"
							color="gray.500"
							pb="8px"
							_selected={{
								color: 'primary.600',
								borderColor: 'primary.500',
							}}
						>
							<Icon as={FiServer} mr="6px" boxSize="12px" />
							{__('Response', 'everest-forms-pro')}
						</Tab>
					</TabList>
					<TabPanels>
						<TabPanel p="0">
							<CodeBlock value={prettyRequest} label="request.json">
								{prettyRequest}
							</CodeBlock>
						</TabPanel>
						<TabPanel p="0">
							<CodeBlock value={prettyResponse} label="response.json">
								{prettyResponse}
							</CodeBlock>
						</TabPanel>
					</TabPanels>
				</Box>
			</Tabs>
		</Stack>
	);
};

export default ApiLogView;
