import { CalendarIcon, CloseIcon, SearchIcon } from '@chakra-ui/icons';
import {
	Box,
	Button,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __, _x } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import {
	addDays,
	endOfDay,
	endOfWeek,
	format,
	startOfDay,
	startOfWeek,
	subDays,
} from 'date-fns';
import React, { useEffect, useRef, useState } from 'react';
import { DateRangePicker } from 'react-date-range';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';
import { Controller, useForm } from 'react-hook-form';
import { useOnType } from 'use-ontype';
import './DatePickerStyles.css';

const ApiLogsFilter: React.FC = ({
	searchQuery,
	setSearchQuery,
	handleBulkAction,
}: any) => {
	const [daterange, setDateRange] = useState([
		{
			startDate: subDays(new Date(), -30),
			endDate: new Date(),
			key: 'selection',
		},
	]);
	const [tempDateRange, setTempDateRange] = useState(daterange);
	const [actionType, setActionType] = useState('');
	const [showDateRangePicker, setShowDateRangePicker] = useState(false);
	const { handleSubmit, control, setValue } = useForm();
	const datePickerRef = useRef<HTMLDivElement>(null);

	useEffect(() => {
		const handleClickOutside = (event: MouseEvent) => {
			if (
				datePickerRef.current &&
				!datePickerRef.current.contains(event.target as Node)
			) {
				setTempDateRange(daterange);
				setShowDateRangePicker(false);
			}
		};

		if (showDateRangePicker) {
			document.addEventListener('mousedown', handleClickOutside);
		}

		return () => {
			document.removeEventListener('mousedown', handleClickOutside);
		};
	}, [showDateRangePicker, daterange]);

	const onSearchItem = useOnType(
		{
			onTypeFinish: (val: string) => {
				setSearchQuery({ ...searchQuery, searchByItem: val });
			},
		},
		800,
	);

	const onChange = (data: any) => {
		setSearchQuery({
			...searchQuery,
			searchByStatus: data.searchByStatus?.value,
		});
	};

	const getFormattedDateRange = () => {
		const { startDate, endDate } = daterange[0];
		return `${format(startDate, 'MM/dd/yyyy')} - ${format(endDate, 'MM/dd/yyyy')}`;
	};

	const staticRanges = [
		{
			label: __('Today', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfDay(new Date()),
				endDate: endOfDay(new Date()),
			}),
			isSelected: () => false,
		},
		{
			label: __('Yesterday', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfDay(addDays(new Date(), -1)),
				endDate: endOfDay(addDays(new Date(), -1)),
			}),
			isSelected: () => false,
		},
		{
			label: __('This Week', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfWeek(new Date()),
				endDate: endOfWeek(new Date()),
			}),
			isSelected: () => false,
		},
		{
			label: __('Last Week', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfWeek(addDays(new Date(), -7)),
				endDate: endOfWeek(addDays(new Date(), -7)),
			}),
			isSelected: () => false,
		},
		{
			label: __('Last 7 Days', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfDay(addDays(new Date(), -7)),
				endDate: endOfDay(new Date()),
			}),
			isSelected: () => false,
		},
		{
			label: __('Last 30 Days', 'everest-forms-pro'),
			range: () => ({
				startDate: startOfDay(addDays(new Date(), -30)),
				endDate: endOfDay(new Date()),
			}),
			isSelected: () => false,
		},
	];

	return (
		<form>
			<Stack
				minW="917px"
				minHeight="62px"
				padding="0px 0px 24px 0px"
				gap="16px"
				direction="row"
			>
				<Stack direction="row" gap="16px" flex={1}>
					<Box minW="170px">
						<Select
							placeholder={_x('Bulk Actions', 'everest-forms-pro')}
							options={[
								{
									label: __('Delete', 'everest-forms-pro'),
									value: 'delete',
								},
							]}
							onChange={(option) => setActionType(option?.value)}
							isClearable
							isSearchable={false}
							chakraStyles={{
								dropdownIndicator: (provided) => ({
									...provided,
									bg: 'transparent',
								}),
								indicatorSeparator: (provided) => ({
									...provided,
									display: 'none',
								}),
								option: (provided, state) => ({
									...provided,
									fontSize: '13px',
								}),
								placeholder: (provided) => ({
									...provided,
									whiteSpace: 'nowrap',
									overflow: 'hidden',
									textOverflow: 'ellipsis',
								}),
								singleValue: (provided) => ({
									...provided,
									whiteSpace: 'nowrap',
								}),
							}}
						/>
					</Box>

					<Button
						colorScheme="primary"
						variant={'outline'}
						onClick={() => handleBulkAction({ action: actionType })}
					>
						<Text fontWeight="500" size="13px" lineHeight="19.5px">
							{__('Apply', 'everest-forms-pro')}
						</Text>
					</Button>
				</Stack>
				<HStack>
					<InputGroup width={'2xs'}>
						<InputLeftElement pointerEvents="none">
							<SearchIcon h="3.5" w="3.5" color="gray.400" />
						</InputLeftElement>
						<Input
							placeholder={__('Search by item', 'everest-forms-pro')}
							{...onSearchItem}
						/>
					</InputGroup>
					<Box position="relative" ref={datePickerRef}>
						<InputGroup>
							<InputLeftElement pointerEvents="none">
								<CalendarIcon color="gray.500" />
							</InputLeftElement>
							<Input
								isReadOnly
								bg="white !important"
								color="black"
								cursor="pointer"
								_placeholder={{ color: 'gray.500' }}
								_focus={{ bg: 'white !important', borderColor: 'blue.500' }}
								_hover={{ bg: 'white !important', borderColor: 'gray.400' }}
								placeholder={__('Select Date Range', 'everest-forms-pro')}
								value={getFormattedDateRange()}
								onClick={() => {
									setTempDateRange(daterange);
									setShowDateRangePicker(true);
								}}
							/>
						</InputGroup>
						{showDateRangePicker && (
							<>
								<Box
									position="fixed"
									top="0"
									left="0"
									right="0"
									bottom="0"
									bg="blackAlpha.300"
									zIndex="1000"
									onClick={() => {
										setTempDateRange(daterange);
										setShowDateRangePicker(false);
									}}
								/>
								<Box
									position="absolute"
									top="calc(100% + 8px)"
									right="0"
									zIndex="1001"
									bg="white !important"
									boxShadow="0 10px 40px rgba(0, 0, 0, 0.15)"
									borderRadius="md"
									overflow="hidden"
									border="1px solid"
									borderColor="gray.200"
								>
									<Box
										p={2}
										borderBottom="1px solid"
										borderColor="gray.200"
										display="flex"
										justifyContent="space-between"
										alignItems="center"
										bg="gray.25"
									>
										<Text fontSize="lg" fontWeight="600" color="gray.700">
											{__('Select Date Range', 'everest-forms-pro')}
										</Text>
										<IconButton
											aria-label="Close date picker"
											icon={<CloseIcon />}
											size="sm"
											variant="ghost"
											onClick={() => setShowDateRangePicker(false)}
										/>
									</Box>
									<DateRangePicker
										onChange={(item) => {
											setTempDateRange([item.selection]);
										}}
										showSelectionPreview={true}
										moveRangeOnFirstSelection={false}
										months={2}
										ranges={tempDateRange}
										direction="horizontal"
										preventSnapRefocus={true}
										calendarFocus="backwards"
										staticRanges={staticRanges}
										inputRanges={[]}
									/>
									<Box
										p={2}
										borderTop="1px solid"
										borderColor="gray.200"
										display="flex"
										justifyContent="flex-end"
										gap={2}
									>
										<Button
											size="sm"
											variant="ghost"
											onClick={() => {
												setTempDateRange(daterange);
												setShowDateRangePicker(false);
											}}
										>
											{__('Cancel', 'everest-forms-pro')}
										</Button>
										<Button
											size="sm"
											colorScheme="primary"
											onClick={() => {
												setDateRange(tempDateRange);
												setSearchQuery({
													...searchQuery,
													searchByDates: tempDateRange[0],
												});
												setValue('searchByDate', tempDateRange[0]);
												setShowDateRangePicker(false);
											}}
										>
											{__('Apply', 'everest-forms-pro')}
										</Button>
									</Box>
								</Box>
							</>
						)}
					</Box>

					<Controller
						name="searchByStatus"
						control={control}
						render={({ field: { onChange: onChangeValue, value } }) => (
							<Select
								onChange={(...args: any[]) => {
									onChangeValue(...args);
									handleSubmit(onChange)();
								}}
								value={value}
								options={[
									{
										label: _x('OK', 'everest-forms-pro'),
										value: 200,
									},
									{
										label: _x('Created', 'everest-forms-pro'),
										value: 201,
									},
									{
										label: _x('Accepted', 'everest-forms-pro'),
										value: 202,
									},
									{
										label: _x('No Content', 'everest-forms-pro'),
										value: 204,
									},
									{
										label: _x('Moved Permanently', 'everest-forms-pro'),
										value: 301,
									},
									{
										label: _x('Found', 'everest-forms-pro'),
										value: 302,
									},
									{
										label: _x('Not Modified', 'everest-forms-pro'),
										value: 304,
									},
									{
										label: _x('Bad Request', 'everest-forms-pro'),
										value: 400,
									},
									{
										label: _x('Unauthorized', 'everest-forms-pro'),
										value: 401,
									},
									{
										label: _x('Forbidden', 'everest-forms-pro'),
										value: 403,
									},
									{
										label: _x('Not Found', 'everest-forms-pro'),
										value: 404,
									},
									{
										label: _x('Method Not Allowed', 'everest-forms-pro'),
										value: 405,
									},
									{
										label: _x('Conflict', 'everest-forms-pro'),
										value: 409,
									},
									{
										label: _x('Gone', 'everest-forms-pro'),
										value: 410,
									},
									{
										label: _x('Internal Server Error', 'everest-forms-pro'),
										value: 500,
									},
									{
										label: _x('Bad Gateway', 'everest-forms-pro'),
										value: 502,
									},
									{
										label: _x('Service Unavailable', 'everest-forms-pro'),
										value: 503,
									},
									{
										label: _x('Gateway Timeout', 'everest-forms-pro'),
										value: 504,
									},
								]}
								placeholder={_x('Status', 'everest-forms-pro')}
								isClearable
								isSearchable={false}
								chakraStyles={{
									dropdownIndicator: (provided) => ({
										...provided,
										bg: 'transparent',
									}),
									indicatorSeparator: (provided) => ({
										...provided,
										display: 'none',
									}),
									option: (provided, state) => ({
										...provided,
										fontSize: '13px',
									}),
								}}
							/>
						)}
					/>
				</HStack>
			</Stack>
		</form>
	);
};

export default ApiLogsFilter;
