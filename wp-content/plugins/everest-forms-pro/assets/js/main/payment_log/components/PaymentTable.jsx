import {
	Pagination,
	PaginationContainer,
	PaginationNext,
	PaginationPage,
	PaginationPageGroup,
	PaginationPrevious,
	PaginationSeparator,
	usePagination,
} from '@ajna/pagination';
import { SearchIcon } from '@chakra-ui/icons';
import {
	Box,
	Button,
	Checkbox,
	Flex,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	Link,
	Skeleton,
	Stack,
	Table,
	Tbody,
	Td,
	Text,
	Th,
	Thead,
	Tr,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import { useEffect, useState } from 'react';
import {
	FaAngleDoubleLeft,
	FaAngleDoubleRight,
	FaAngleLeft,
	FaAngleRight,
	FaSort,
	FaSortDown,
	FaSortUp,
} from 'react-icons/fa';
import { useOnType } from 'use-ontype';
import { deletePaymentEntries, getActiveGateways, getFormList, getPaymentEntryData } from './PaymentApi';

const isCompletePaymentStatus = (status) =>
	typeof status === 'string' && status.toLowerCase() === 'complete';

const PaymentTransactionCell = ({ entry }) => {
	const id = entry?.payment_transaction;

	if (!id || !isCompletePaymentStatus(entry?.status)) {
		return <Text fontSize="sm">—</Text>;
	}

	return (
		<Text fontSize="sm" wordBreak="break-all" whiteSpace="normal" lineHeight="short" title={id}>
			{id}
		</Text>
	);
};

const PaymentTable = () => {
	const [forms, setForms] = useState([]);
	const [gateways, setGateways] = useState([]);
	const [isGatewaysLoading, setIsGatewaysLoading] = useState(true);
	const [paymentEntry, setPaymentEntry] = useState([]);
	const [paymentLogCount, setPaymentLogCount] = useState(0);
	const [formId, setFormId] = useState(null);
	const [paymentStatus, setPaymentStatus] = useState(null);
	const [gateway, setGateway] = useState(null);
	const [searchQuery, setSearchQuery] = useState('');
	const [isLoading, setIsLoading] = useState(true);

	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkAction, setBulkAction] = useState('');
	const [refreshKey, setRefreshKey] = useState(0);
	const [sortOrder, setSortOrder] = useState('desc');

	const tableHeading = [
		'Entry ID',
		'Form',
		'Customer',
		'Email',
		'Total Amount',
		'Gateway',
		'Status',
		'Transaction Id',
		'Created Date',
	];

	const handleSortToggle = () => {
		setCurrentPage(1);
		setSortOrder((prev) => (prev === 'desc' ? 'asc' : 'desc'));
	};

	const status = [
		{ label: 'Complete', value: 'complete' },
		{ label: 'Pending', value: 'pending' },
		{ label: 'Failed', value: 'failed' },
	];
	const ensureGatewayOption = (options, value, label) => {
		const list = Array.isArray(options) ? options : [];
		const exists = list.some((opt) => String(opt?.value) === String(value));
		if (exists) {
			return list;
		}
		return [...list, { value, label }].sort((a, b) =>
			String(a.label).localeCompare(String(b.label)),
		);
	};

	const outerLimit = 2;
	const innerLimit = 2;

	const {
		pages,
		pagesCount,
		offset,
		currentPage,
		setCurrentPage,
		isDisabled,
		pageSize,
	} = usePagination({
		total: paymentLogCount,
		limits: {
			outer: outerLimit,
			inner: innerLimit,
		},
		initialState: {
			pageSize: 10,
			isDisabled: false,
			currentPage: 1,
		},
	});

	const handlePageChange = (nextPage) => {
		setCurrentPage(nextPage);
	};

	const handleLogByForm = (event) => {
		const formId = Number(event ? event.value : 0);
		setCurrentPage(1);
		setFormId(formId);
	};

	const handleLogByStatus = (event) => {
		const status = event ? event.value : '';
		setCurrentPage(1);
		setPaymentStatus(status);
	};

	const handleLogByGateway = (event) => {
		const gw = event ? event.value : '';
		setCurrentPage(1);
		setGateway(gw);
	};

	const searchLog = useOnType(
		{
			onTypeFinish: (val) => {
				setCurrentPage(1);
				setSearchQuery(val);
			},
		},
		800,
	);

	const safePaymentEntry = paymentEntry ?? [];
	const isAllSelected =
		safePaymentEntry.length > 0 &&
		selectedRows.length === safePaymentEntry.length;
	const isIndeterminate =
		selectedRows.length > 0 && selectedRows.length < safePaymentEntry.length;

	const handleSelectAll = (isChecked) => {
		if (isChecked) {
			setSelectedRows((paymentEntry ?? []).map((e) => e.submission_id));
		} else {
			setSelectedRows([]);
		}
	};

	const handleSelectRow = (id, isChecked) => {
		setSelectedRows((prev) =>
			isChecked ? [...prev, id] : prev.filter((rowId) => rowId !== id),
		);
	};

	const handleBulkApply = () => {
		if (bulkAction === 'delete' && selectedRows.length > 0) {
			deletePaymentEntries(selectedRows).then(() => {
				setSelectedRows([]);
				setBulkAction('');
				setCurrentPage(1);
				setRefreshKey((k) => k + 1);
			});
		}
	};

	useEffect(() => {
		setIsLoading(true);
		getPaymentEntryData(
			pageSize,
			offset,
			formId,
			paymentStatus,
			searchQuery,
			gateway || '',
			'date_created',
			sortOrder,
		)
			.then((res) => {
				if (res && res.success) {
					setPaymentEntry(res.payment_log?.result ?? []);
					setPaymentLogCount(res.payment_log?.total_count ?? 0);
					setSelectedRows([]);
				} else {
					setPaymentEntry([]);
					setPaymentLogCount(0);
				}
				setIsLoading(false);
			})
			.catch(() => {
				setPaymentEntry([]);
				setPaymentLogCount(0);
				setIsLoading(false);
			});
	}, [currentPage, pageSize, offset, formId, paymentStatus, searchQuery, gateway, refreshKey, sortOrder]);

	useEffect(() => {
		getFormList()
			.then((res) => {
				if (res && res.success) {
					setForms(res.form_list ?? []);
				}
			})
			.catch(() => {
				setForms([]);
			});
		getActiveGateways()
			.then((res) => {
				if (res && res.success) {
					setGateways(res.gateways ?? []);
				}
			})
			.catch(() => {
				setGateways([]);
			})
			.finally(() => {
				setIsGatewaysLoading(false);
			});
	}, []);

	useEffect(() => {
		if (
			gateway &&
			!gateways.some((g) => String(g.value) === String(gateway))
		) {
			setGateway(null);
		}
	}, [gateways, gateway]);

	return (
		<Box bg="white" borderRadius={10} minW="md">
			<Stack direction="column" gap="30px">
				<Flex justify="space-between" align="center">
					<Flex align="center" gap="16px">
						<Text fontSize="xl" fontWeight="600" color="#383838">
							{__('Payments', 'everest-forms-pro')}
						</Text>
						<Box minW="200px">
							<Select
								placeholder={__('Select Form', 'everest-forms-pro')}
								colorScheme="primary"
								options={forms}
								isClearable
								isSearchable={false}
								onChange={handleLogByForm}
								chakraStyles={{
									control: (provided) => ({
										...provided,
										borderRadius: '4px',
										borderColor: '#e1e1e1',
										fontSize: '14px',
										color: '#383838',
										_hover: {
											borderColor: 'primary.400',
										},
									}),
									dropdownIndicator: (provided) => ({
										...provided,
										bg: 'transparent',
									}),
									indicatorSeparator: (provided) => ({
										...provided,
										display: 'none',
									}),
									placeholder: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									singleValue: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									option: (provided) => ({
										...provided,
										fontSize: '13px',
									}),
								}}
							/>
						</Box>
						<Button
							as="a"
							href={`${evf_payment_log.adminURL}?page=evf-settings&tab=payment`}
							size="sm"
							height="38px"
							colorScheme="primary"
							variant="outline"
							_hover={{
								bg: 'transparent',
								color: 'primary.500',
							}}
							leftIcon={
								<svg
									width="14"
									height="14"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								>
									<circle cx="12" cy="12" r="3" />
									<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
								</svg>
							}
						>
							{__('Payment Settings', 'everest-forms-pro')}
						</Button>
					</Flex>
					<HStack>
							<InputGroup width="2xs">
								<InputLeftElement pointerEvents="none">
									<SearchIcon h="3.5" w="3.5" color="gray.400" />
								</InputLeftElement>
								<Input
									placeholder={__('Search...', 'everest-forms-pro')}
									_placeholder={{ color: '#383838' }}
									borderColor="#e1e1e1"
									focusBorderColor="primary.400"
									borderRadius="4px"
									fontSize="14px"
									color="#383838"
									{...searchLog}
								/>
							</InputGroup>
						</HStack>
				</Flex>

				<Box display="flex" justifyContent="space-between">
					{/* Bulk Actions */}
					<Flex alignItems="center" gap="8px">
						<Box minW="160px">
							<Select
								placeholder={__('Bulk Actions', 'everest-forms-pro')}
								colorScheme="primary"
								value={bulkAction ? { label: __('Delete', 'everest-forms-pro'), value: 'delete' } : null}
								options={[{ label: __('Delete', 'everest-forms-pro'), value: 'delete' }]}
								isClearable
								isSearchable={false}
								onChange={(option) => setBulkAction(option ? option.value : '')}
								chakraStyles={{
									control: (provided) => ({
										...provided,
										borderRadius: '4px',
										borderColor: '#e1e1e1',
										fontSize: '14px',
										color: '#383838',
										_hover: {
											borderColor: 'primary.400',
										},
									}),
									dropdownIndicator: (provided) => ({
										...provided,
										bg: 'transparent',
									}),
									indicatorSeparator: (provided) => ({
										...provided,
										display: 'none',
									}),
									placeholder: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									singleValue: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									option: (provided) => ({
										...provided,
										fontSize: '13px',
									}),
								}}
							/>
						</Box>
						<Button
							size="sm"
							height="38px"
							colorScheme="primary"
							variant="outline"
							onClick={handleBulkApply}
						>
							{__('Apply', 'everest-forms-pro')}
						</Button>
					</Flex>
					<Stack direction="row" gap="16px">
						<Box minW="160px">
							<Select
								placeholder={__('Select Status', 'everest-forms-pro')}
								colorScheme="primary"
								options={status}
								isClearable
								isSearchable={false}
								onChange={handleLogByStatus}
								chakraStyles={{
									control: (provided) => ({
										...provided,
										borderRadius: '4px',
										borderColor: '#e1e1e1',
										fontSize: '14px',
										color: '#383838',
										_hover: {
											borderColor: 'primary.400',
										},
									}),
									dropdownIndicator: (provided) => ({
										...provided,
										bg: 'transparent',
									}),
									indicatorSeparator: (provided) => ({
										...provided,
										display: 'none',
									}),
									placeholder: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									singleValue: (provided) => ({
										...provided,
										fontSize: '14px',
										color: '#383838',
									}),
									option: (provided) => ({
										...provided,
										fontSize: '13px',
									}),
								}}
							/>
						</Box>
						{(isGatewaysLoading || gateways.length > 0) && (
							<Box minW="170px">
								<Select
									placeholder={__(
										'Select Payment Gateway',
										'everest-forms-pro',
									)}
									colorScheme="primary"
									options={gateways}
									isDisabled={isGatewaysLoading}
									isClearable={!isGatewaysLoading}
									isSearchable={false}
									onChange={handleLogByGateway}
									chakraStyles={{
										control: (provided) => ({
											...provided,
											borderRadius: '4px',
											borderColor: '#e1e1e1',
											fontSize: '14px',
											color: '#383838',
											_hover: {
												borderColor: 'primary.400',
											},
										}),
										dropdownIndicator: (provided) => ({
											...provided,
											bg: 'transparent',
										}),
										indicatorSeparator: (provided) => ({
											...provided,
											display: 'none',
										}),
										placeholder: (provided) => ({
											...provided,
											fontSize: '14px',
											color: '#383838',
										}),
										singleValue: (provided) => ({
											...provided,
											fontSize: '14px',
											color: '#383838',
										}),
										option: (provided) => ({
											...provided,
											fontSize: '13px',
										}),
									}}
								/>
							</Box>
						)}
					</Stack>
				</Box>

				{/* Table */}
				<Box
					borderWidth="1px"
					borderColor="grey.50"
					rounded="md"
					overflow="auto"
				>
					<Table
						variant="simple"
						size="md"
						sx={{
							'th, td': {
								paddingLeft: '12px',
								paddingRight: '12px',
							},
						}}
					>
						<Thead>
							<Tr height={'56px'} textAlign={'left'} bg="white">
							<Th w="40px">
								<Checkbox
									isChecked={isAllSelected}
									isIndeterminate={isIndeterminate}
									onChange={(e) => handleSelectAll(e.target.checked)}
									colorScheme="primary"
								/>
							</Th>
								{tableHeading?.map((heading, index) => {
									const isDateCol = heading === 'Created Date';
									const isLastCol = index === tableHeading.length - 1;
									return (
										<Th
											key={index}
											textTransform="none"
											maxW={isLastCol ? '160px' : undefined}
											w={isLastCol ? '160px' : undefined}
											cursor={isDateCol ? 'pointer' : undefined}
											onClick={isDateCol ? handleSortToggle : undefined}
											_hover={isDateCol ? { bg: 'gray.50' } : undefined}
										>
											<Flex alignItems="center" gap="4px">
												<Text fontSize={'sm'} fontWeight="600" color="grey.500">
													{__(heading, 'everest-forms-pro')}
												</Text>
												{isDateCol && (
													<Box color="grey.400" fontSize="10px">
														{sortOrder === 'asc' ? <FaSortUp /> : sortOrder === 'desc' ? <FaSortDown /> : <FaSort />}
													</Box>
												)}
											</Flex>
										</Th>
									);
								})}
							</Tr>
						</Thead>

						<Tbody>
							{isLoading ? (
								Array.from({ length: 8 }).map((_, rowIndex) => (
									<Tr
										key={rowIndex}
										height="64px"
										bg={rowIndex % 2 === 0 ? 'white' : 'primary.15'}
									>
										<Td w="40px">
											<Skeleton h="14px" w="14px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="60px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="120px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="90px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="140px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="70px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="75px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="55px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="110px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="100px" borderRadius="sm" />
										</Td>
									</Tr>
								))
							) : paymentLogCount === 0 ? (
								<Tr>
									<Td colSpan={tableHeading.length + 1}>
										<Flex justifyContent={'center'} align="center" py={6}>
											<img
												height={'236px'}
												width={'262px'}
												src={evf_payment_log.not_found_image}
											/>
										</Flex>
										<Stack marginTop={'16px'} textAlign={'center'} gap={0}>
											<Text
												margin={0}
												fontSize="lg"
												color="#383838"
												fontWeight={600}
											>
												{__('No payment logs found', 'everest-forms')}
											</Text>
										</Stack>
									</Td>
								</Tr>
							) : (
								safePaymentEntry?.map((entry, index) => (
									<Tr
										key={index}
										height="64px"
										textAlign="left"
										bg={index % 2 === 0 ? 'white' : 'primary.15'}
									>
										<Td w="40px">
											<Checkbox
												isChecked={selectedRows.includes(entry.submission_id)}
												onChange={(e) =>
													handleSelectRow(entry.submission_id, e.target.checked)
												}
												colorScheme="primary"
											/>
										</Td>
										<Td>
											<Text fontSize="sm">
												{__(entry.submission_id, 'everest-forms-pro')}
											</Text>
										</Td>

										<Td>
											<Text fontSize="sm">
												{__(entry.form, 'everest-forms-pro')}
											</Text>
										</Td>

										<Td>
											<Text fontSize="sm">
												{entry.customer || '—'}
											</Text>
										</Td>

										<Td>
											{entry.customer_email ? (
												<Link
													href={`mailto:${entry.customer_email}`}
													fontSize="sm"
													color="primary.500"
													wordBreak="break-all"
													title={entry.customer_email}
												>
													{entry.customer_email}
												</Link>
											) : (
												<Text fontSize="sm">—</Text>
											)}
										</Td>

										<Td>
											<Text fontSize="sm">
												{__(entry.total_amount, 'everest-forms-pro') +
													' ' +
													entry.payment_currency}
											</Text>
										</Td>

										<Td>
											<Text fontSize="sm">
												{__(entry.payment_gateway, 'everest-forms-pro')}
											</Text>
										</Td>

										<Td>
											{(() => {
												const normalizedStatus = String(
													entry.status || '',
												).toLowerCase();
												const isCancelledStatus = [
													'cancelled',
													'canceled',
													'cancled',
													'cancel',
												].includes(normalizedStatus);
												const statusColor =
													normalizedStatus === 'failed'
														? '#DC3545'
														: normalizedStatus === 'complete' ||
															  normalizedStatus === 'completed'
															? '#28A745'
															: isCancelledStatus
																? '#7e7d77'
																: '#FFC700';

												return (
											<Text
												fontSize="xs"
												fontWeight="400"
												display="inline-block"
												border="1px solid"
												borderColor={statusColor}
												color={statusColor}
												borderRadius="full"
												px={3}
												py={1}
											>
												{__(entry.status, 'everest-forms-pro')}
											</Text>
												);
											})()}
										</Td>

										<Td>
											<PaymentTransactionCell entry={entry} />
										</Td>

										<Td whiteSpace="nowrap">
											<Text fontSize="sm">
												{entry.date_created
													? new Date(entry.date_created).toLocaleDateString(undefined, {
															year: 'numeric',
															month: 'short',
															day: 'numeric',
													  })
													: '—'}
											</Text>
										</Td>
									</Tr>
								))
							)}
						</Tbody>
					</Table>
				</Box>
			</Stack>

			{/* Pagination */}
			{!isLoading && paymentLogCount > 10 && (
				<Box borderTop="1px solid" borderColor="grey.50" px={2} py={2} mt={3}>
					<Flex alignItems="center" justify="space-between">
						<Text fontSize="sm" color="grey.300">
							{__('Showing', 'everest-forms-pro')}{' '}
							{paymentLogCount === 0 ? 0 : (currentPage - 1) * pageSize + 1}-
							{Math.min(currentPage * pageSize, paymentLogCount)}{' '}
							{__('of', 'everest-forms-pro')} {paymentLogCount}{' '}
							{__('entries', 'everest-forms-pro')}
						</Text>

						<Flex alignItems="center" gap="2px">
							<IconButton
								aria-label={__('First page', 'everest-forms-pro')}
								icon={<FaAngleDoubleLeft />}
								variant="ghost"
								colorScheme="gray"
								size="sm"
								fontSize="12px"
								isDisabled={isDisabled || currentPage === 1}
								onClick={() => handlePageChange(1)}
							/>

							<Pagination
								pagesCount={pagesCount}
								currentPage={currentPage}
								isDisabled={isDisabled}
								onPageChange={handlePageChange}
							>
								<PaginationContainer gap="2px">
									<PaginationPrevious
										variant="ghost"
										colorScheme="gray"
										size="sm"
										minW={8}
										h={8}
										fontSize="12px"
									>
										<FaAngleLeft />
									</PaginationPrevious>

									<PaginationPageGroup
										align="center"
										separator={
											<PaginationSeparator
												bg="transparent"
												color="grey.300"
												fontSize="sm"
												w={8}
												h={8}
												jumpSize={11}
											/>
										}
									>
										{pages?.map((page) => (
											<PaginationPage
												key={`pagination_page_${page}`}
												page={page}
												w={8}
												h={8}
												fontSize="sm"
												fontWeight="400"
												bg="transparent"
												borderRadius="md"
												color="grey.300"
												_hover={{ bg: 'primary.25' }}
												_current={{
													bg: 'primary.400',
													color: 'white',
													fontWeight: '500',
													borderRadius: 'md',
												}}
											/>
										))}
									</PaginationPageGroup>

									<PaginationNext
										variant="ghost"
										colorScheme="gray"
										size="sm"
										minW={8}
										h={8}
										fontSize="12px"
									>
										<FaAngleRight />
									</PaginationNext>
								</PaginationContainer>
							</Pagination>

							<IconButton
								aria-label={__('Last page', 'everest-forms-pro')}
								icon={<FaAngleDoubleRight />}
								variant="ghost"
								colorScheme="gray"
								size="sm"
								fontSize="12px"
								isDisabled={isDisabled || currentPage === pagesCount}
								onClick={() => handlePageChange(pagesCount)}
							/>
						</Flex>
					</Flex>
				</Box>
			)}
		</Box>
	);
};

export default PaymentTable;
