import {
	Box,
	Button,
	Checkbox,
	Flex,
	IconButton,
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
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { deleteLogs, getApiLogs, getLogContent } from '../api/api';
import { apiLogScriptData } from './../utils/global';
import ApiLogsFilter from './ApiLogsFilter';
import MailLogView from './ApiLogView';
import DisplayModal from './DisplayModal';

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
import {
	FaAngleDoubleLeft,
	FaAngleDoubleRight,
	FaAngleLeft,
	FaAngleRight,
} from 'react-icons/fa';

const ApiLogsTable = () => {
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [singleViewData, setSingleViewData] = useState({});
	const toast = useToast();
	const { builderURL, viewEntryURL, statusCodes } = apiLogScriptData;
	const [logs, setLogs] = useState([]);
	const [isCheckAll, setIsCheckAll] = useState(false);
	const [list, setList] = useState<(string | number)[]>([]);
	const [isCheck, setIsCheck] = useState<(string | number)[]>([]);
	const [totalCount, setTotalCount] = useState(0);

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
		total: totalCount,
		limits: { outer: outerLimit, inner: innerLimit },
		initialState: { pageSize: 10, isDisabled: false, currentPage: 1 },
	});

	const [searchQuery, setSearchQuery] = useState({
		limit: pageSize,
		offset: offset,
	});

	const { data, isLoading, refetch } = useQuery({
		queryKey: ['apilogs', searchQuery],
		queryFn: getApiLogs,
		onSuccess: (res: any) => {
			setLogs(res.result);
			setTotalCount(res.total_count);
			if (res.total_count <= pageSize) setCurrentPage(1);
			setList(res.result.map((log: any) => log.id));
		},
	});

	const onClickCheckAll = () => {
		setIsCheckAll(!isCheckAll);
		setIsCheck(!isCheckAll ? list : []);
	};

	const handleClick = (e: React.ChangeEvent<HTMLInputElement>) => {
		const { id, checked } = e.target;
		setIsCheckAll(false);
		setIsCheck((prev) =>
			checked ? [...prev, id] : prev.filter((item) => item !== id),
		);
	};

	const handleBulkAction = (props: any) => {
		if ('' === props.action) return;
		if ('delete' === props.action) {
			if (isCheck.length === 0) {
				toast({
					title: __('Please select atleast one log', 'everest-forms-pro'),
				});
			}
			deleteMutation.mutate(isCheck);
		}
	};

	const filterProps = {
		searchQuery,
		setSearchQuery,
		handleBulkAction,
	};

	const handlePageChange = (nextPage: number) => {
		setCurrentPage(nextPage);
		setSearchQuery({ ...searchQuery, offset: nextPage });
	};

	const deleteMutation = useMutation({
		mutationFn: deleteLogs,
		onSuccess: () => {
			toast({
				title: __('Deletion successfully!!', 'everest-forms-pro'),
				status: 'success',
				isClosable: true,
				duration: 3000,
			});
			refetch();
		},
		onError: () => {
			toast({
				title: __('Deletion Failed!!', 'everest-forms-pro'),
				status: 'error',
				isClosable: true,
				duration: 3000,
			});
		},
	});

	const viewApiLogMutation = useMutation({
		mutationFn: getLogContent,
		onSuccess: (res: any) => {
			setSingleViewData(res[0]);
		},
		onError: () => {
			toast({
				title: __('Failed to load log content', 'easy-mail-smtp'),
				status: 'error',
				isClosable: true,
				duration: 9000,
			});
		},
	});

	const singleView = (e: React.MouseEvent<HTMLButtonElement>) => {
		onOpen();
		const { id } = e.currentTarget;
		viewApiLogMutation.mutate({ id });
	};

	const tableHeadings = [
		__('Entry Id', 'everest-forms-pro'),
		__('Form Name', 'everest-forms-pro'),
		__('Source', 'everest-forms-pro'),
		__('Status', 'everest-forms-pro'),
		__('Log', 'everest-forms-pro'),
		__('Date', 'everest-forms-pro'),
		__('Action', 'everest-forms-pro'),
	];

	return (
		<Stack direction="column" w="100%" minW="0" borderRadius="7px" gap="30px">
			<ApiLogsFilter {...filterProps} />

			<Stack direction="column" gap="32px">
				<Box
					borderWidth="1px"
					borderColor="grey.50"
					rounded="md"
					overflow="auto"
				>
					<Table variant="simple" size="md" w="100%" tableLayout="fixed">
						<Thead>
							<Tr height="56px" textAlign="left" bg="white">
								<Th w="40px">
									<Checkbox onChange={onClickCheckAll} isChecked={isCheckAll} />
								</Th>
								{tableHeadings.map((heading, index) => (
									<Th key={index} textTransform="none">
										<Text fontSize="sm" fontWeight="600" color="grey.500">
											{heading}
										</Text>
									</Th>
								))}
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
										<Td>
											<Skeleton h="14px" w="14px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="70%" maxW="60px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="80%" maxW="120px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="70%" maxW="70px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="60%" maxW="55px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="90%" maxW="160px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="80%" maxW="90px" borderRadius="sm" />
										</Td>
										<Td>
											<Skeleton h="12px" w="50%" maxW="32px" borderRadius="sm" />
										</Td>
									</Tr>
								))
							) : logs.length > 0 ? (
								logs.map((log: any, index: number) => (
									<Tr
										key={log.id}
										height="64px"
										textAlign="left"
										bg={index % 2 === 0 ? 'white' : 'primary.15'}
									>
										<Td>
											<Checkbox
												key={log.id}
												type="checkbox"
												id={log.id}
												onChange={(e) => handleClick(e)}
												isChecked={isCheck.includes(log.id)}
											/>
										</Td>
										<Td>
											<Link
												href={`${viewEntryURL}&form_id=${log.form_id}&view-entry=${log.entry_id}`}
												target="_blank"
												textDecoration="underline"
											>
												<Text fontSize="sm">#{log.entry_id}</Text>
											</Link>
										</Td>
										<Td>
											<Link
												href={`${builderURL}&form_id=${log.form_id}`}
												target="_blank"
												textDecoration="underline"
											>
												<Text fontSize="sm">{log.form_name}</Text>
											</Link>
										</Td>
										<Td>
											<Text fontSize="sm">{log.source}</Text>
										</Td>
										<Td>
											<Text
												fontSize="sm"
												fontWeight="400"
												display="inline-block"
												border="1px solid"
												borderColor={
													log.status === 'error' || log.status === 'failed'
														? '#DC3545'
														: log.status === 'success'
															? '#28A745'
															: '#FFC700'
												}
												color={
													log.status === 'error' || log.status === 'failed'
														? '#DC3545'
														: log.status === 'success'
															? '#28A745'
															: '#FFC700'
												}
												borderRadius="full"
												px={3}
												py={1}
											>
												{log.status && statusCodes[log.status]}
											</Text>
										</Td>
										<Td>
											<Text fontSize="sm">{log.log}</Text>
										</Td>
										<Td>
											<Text fontSize="sm">{log.created_date}</Text>
										</Td>
										<Td>
											<Button
												border="none"
												bg="none"
												size="xs"
												_hover={{ bg: 'none', border: 'none' }}
												_active={{ bg: 'none', border: 'none' }}
												id={log.id}
												onClick={(e) => singleView(e)}
											>
												<Text
													fontWeight="400"
													fontSize="14px"
													lineHeight="24px"
													color="primary.500"
												>
													{__('View', 'easy-mail-smtp')}
												</Text>
											</Button>
										</Td>
									</Tr>
								))
							) : (
								<Tr>
									<Td colSpan={tableHeadings.length + 1}>
										<Flex
											direction="column"
											justifyContent="center"
											alignItems="center"
											py={6}
											gap={4}
										>
											<img
												height="236px"
												width="262px"
												src={apiLogScriptData.not_found_image}
											/>
											<Text
												margin={0}
												fontSize="lg"
												color="#222222"
												fontWeight={600}
											>
												{__('No api logs found.', 'easy-mail-smtp')}
											</Text>
										</Flex>
									</Td>
								</Tr>
							)}
						</Tbody>
					</Table>
				</Box>
			</Stack>

			{logs.length > 0 && (
				<Box borderTop="1px solid" borderColor="grey.50" px={2} py={2}>
					<Flex alignItems="center" justify="space-between">
						<Text fontSize="sm" color="grey.300">
							{__('Showing', 'everest-forms-pro')}{' '}
							{totalCount === 0 ? 0 : (currentPage - 1) * pageSize + 1}-
							{Math.min(currentPage * pageSize, totalCount)}{' '}
							{__('of', 'everest-forms-pro')} {totalCount}{' '}
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
										{pages?.map((page: any) => (
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

			<DisplayModal
				isOpen={isOpen}
				onClose={onClose}
				applyPadding={false}
				size={{
					base: 'full',
					sm: 'md',
					md: 'xl',
					lg: '2xl',
					xl: '5xl',
				}}
			>
				<MailLogView
					singleLogData={singleViewData}
					isLoading={viewApiLogMutation?.isLoading}
				/>
			</DisplayModal>
		</Stack>
	);
};

export default ApiLogsTable;
