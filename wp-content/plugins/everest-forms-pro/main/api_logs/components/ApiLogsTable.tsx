import {
	Box,
	Button,
	Center,
	Checkbox,
	Flex,
	Link,
	Spinner,
	Stack,
	Table,
	Tbody,
	Td,
	Text,
	Tfoot,
	Th,
	Thead,
	Tr,
	useDisclosure,
	useToast,
} from "@chakra-ui/react";
import React, { ChangeEvent, useEffect, useState } from "react";
import ApiLogsFilter from "./ApiLogsFilter";
import { __ } from "@wordpress/i18n";
import { useMutation, useQuery } from "@tanstack/react-query";
import { deleteLogs, getApiLogs, getLogContent } from "../api/api";
import { apiLogScriptData } from "./../utils/global";
import { Select } from "chakra-react-select";
import DisplayModal from "./DisplayModal";
import MailLogView from "./ApiLogView";

import {
	Pagination,
	PaginationContainer,
	PaginationNext,
	PaginationPage,
	PaginationPageGroup,
	PaginationPrevious,
	PaginationSeparator,
	usePagination,
} from "@ajna/pagination";
import { FaChevronLeft, FaChevronRight } from "react-icons/fa";

const ApiLogsTable = () => {
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [singleViewData, setSingleViewData] = useState({});
	const [isViewLoading, setIsViewLoading] = useState(false);
	const toast = useToast();
	const { builderURL, viewEntryURL, statusCodes } = apiLogScriptData;
	const [logs, setLogs] = useState([]);
	const [isCheckAll, setIsCheckAll] = useState(false);
	const [list, setList] = useState<(string | number)[]>([]);
	const [isCheck, setIsCheck] = useState<(string | number)[]>([]);

	const [totalCount, setTotalCount] = useState(0);
	const mappedOptions = [
		{ label: 5, value: 5 },
		{ label: 10, value: 10 },
		{ label: 25, value: 25 },
		{ label: 50, value: 50 },
	];
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
		setPageSize,
	} = usePagination({
		total: totalCount,
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
	const [searchQuery, setSearchQuery] = useState({
		limit: pageSize,
		offset: offset,
	});
	const { data, isLoading, refetch } = useQuery({
		queryKey: ["apilogs", searchQuery],
		queryFn: getApiLogs,
		onSuccess: (res: any) => {
			setLogs(res.result);
			setTotalCount(res.total_count);
			if (res.total_count <= pageSize) {
				setCurrentPage(1);
			}
			const list_log_id = res.result.map((logs: any) => {
				return logs.id;
			});
			setList(list_log_id);
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
		if ("" === props.action) {
			return;
		}
		if ("delete" === props.action) {
			if (isCheck.length === 0) {
				toast({
					title: __(
						"Please select atleast one log",
						"everest-forms-pro",
					),
				});
			}

			deleteMutation.mutate(isCheck);
		}
	};

	const filterProps = {
		searchQuery: searchQuery,
		setSearchQuery: setSearchQuery,
		handleBulkAction: handleBulkAction,
	};

	const handlePageChange = (nextPage: number) => {
		setCurrentPage(nextPage);
		setSearchQuery({ ...searchQuery, offset: nextPage });
	};

	const handlePageSizeChange = (event: any) => {
		const pageSize = Number(event.value);
		setPageSize(pageSize);
		setSearchQuery({ ...searchQuery, limit: pageSize });
	};
	const deleteMutation = useMutation({
		mutationFn: deleteLogs,
		onSuccess: (res: any) => {
			toast({
				title: __("Deletion successfully!!", "everest-forms-pro"),
				status: "success",
				isClosable: true,
				duration: 3000,
			});
			refetch();
		},
		onError: (error: any) => {
			toast({
				title: __("Deletion Failed!!", "everest-forms-pro"),
				status: "error",
				isClosable: true,
				duration: 3000,
			});
		},
	});
	const viewApiLogMutation = useMutation({
		mutationFn: getLogContent,
		onSuccess: (res: any) => {
			setSingleViewData(res[0]);
			setIsViewLoading(false);
		},
		onError: (res) => {
			setIsViewLoading(false);
			console.log(res);

			toast({
				title: __("Failed to load log content", "easy-mail-smtp"),
				status: "error",
				isClosable: true,
				duration: 9000,
			});
		},
	});
	const singleView = (e: React.MouseEvent<HTMLButtonElement>) => {
		const { id } = e.currentTarget;
		setIsViewLoading(true);
		onOpen();
		viewApiLogMutation.mutate({ id });
	};
	return (
		<Stack direction="column" minW="917px" borderRadius="7px" gap="30px">
			<ApiLogsFilter {...filterProps} />
			<Stack direction="column" padding="0px 0px 24px 0px" gap="32px">
				<Table>
					<Thead>
						<Tr bg="#F0F7FF" padding="12px 16px 12px 16px">
							<Th>
								<Checkbox
									onChange={onClickCheckAll}
									isChecked={isCheckAll}
								/>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Entry Id", "everest-forms-pro")}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Form Name", "everest-forms-pro")}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Source", "everest-forms-pro")}
								</Text>
							</Th>

							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Status", "everest-forms-pro")}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Log", "everest-forms-pro")}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Date", "everest-forms-pro")}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="500"
									fontSize="13px"
									lineHeight="24px"
									color="#383838"
								>
									{__("Action", "everest-forms-pro")}
								</Text>
							</Th>
						</Tr>
					</Thead>
					<Tbody>
						{logs.length > 0 ? (
							logs.map((log: any) => (
								<Tr key={log.id}>
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
											#{log.entry_id}
										</Link>
									</Td>
									<Td>
										<Link
											href={`${builderURL}&form_id=${log.form_id}`}
											target="_blank"
											textDecoration="underline"
										>
											{log.form_name}
										</Link>
									</Td>
									<Td>{log.source}</Td>
									<Td>
										{log.status && statusCodes[log.status]}
									</Td>
									<Td>{log.log}</Td>
									<Td>{log.created_date}</Td>
									<Td>
										<Button
											border="none"
											bg="none"
											_hover={{
												bg: "none",
												border: "none",
											}}
											_active={{
												bg: "none",
												border: "none",
											}}
											id={log.id}
											onClick={(e) => singleView(e)}
										>
											<Text
												fontWeight="400"
												fontSize="12px"
												lineHeight="24px"
												color="primary.500"
											>
												{__("View", "easy-mail-smtp")}
											</Text>
										</Button>
									</Td>
								</Tr>
							))
						) : (
							<Tr border={"none"}>
								<Td colSpan={7}>
									<Box
										display="flex"
										justifyContent="center"
										alignItems="center"
									>
										<Text
											textAlign="center"
											color="gray.500"
										>
											{__(
												"No api logs found.",
												"easy-mail-smtp",
											)}
										</Text>
									</Box>
								</Td>
							</Tr>
						)}
					</Tbody>
				</Table>
			</Stack>
			{logs.length > 0 && (
				<Stack mt={3}>
					<Flex alignItems="center" justify="space-between">
						<Flex alignItems="center">
							<Text fontSize="md" p={"4"}>
								{__("Show per page", "everest-forms-pro")}
							</Text>
							<Select
								onChange={handlePageSizeChange}
								colorScheme="primary"
								isSearchable={false}
								options={mappedOptions}
								defaultValue={mappedOptions[1]}
							/>
						</Flex>
						<Pagination
							pagesCount={pagesCount}
							currentPage={currentPage}
							isDisabled={isDisabled}
							onPageChange={handlePageChange}
						>
							<PaginationContainer justify="space-between" p={4}>
								<PaginationPrevious
									_hover={{ bg: "primary.200" }}
									bg="gray.200"
								>
									<FaChevronLeft />
								</PaginationPrevious>
								<PaginationPageGroup
									align="center"
									separator={
										<PaginationSeparator
											bg="blue.300"
											fontSize="sm"
											w={7}
											jumpSize={11}
										/>
									}
								>
									{pages?.map((page: any) => (
										<PaginationPage
											w={7}
											bg="grey.200"
											key={`pagination_page_${page}`}
											page={page}
											fontSize="sm"
											_hover={{ bg: "primary.200" }}
											_current={{
												bg: "blue.300",
												fontSize: "sm",
												w: 7,
											}}
										/>
									))}
								</PaginationPageGroup>
								<PaginationNext
									_hover={{ bg: "primary.200" }}
									bg="gray.200"
								>
									<FaChevronRight />
								</PaginationNext>
							</PaginationContainer>
						</Pagination>
					</Flex>
				</Stack>
			)}
			<DisplayModal
				title={__("API Log Detail", "everest-forms-pro")}
				isOpen={isOpen}
				onClose={onClose}
				size={{
					base: "full",
					sm: "md",
					md: "xl",
					lg: "2xl",
					xl: "5xl",
				}}
			>
				<MailLogView
					singleLogData={singleViewData}
					isLoading={isViewLoading}
				/>
			</DisplayModal>
		</Stack>
	);
};

export default ApiLogsTable;
