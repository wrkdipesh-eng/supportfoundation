import React, { useEffect, useState } from "react";
import {
	Table,
	Thead,
	Tbody,
	Tr,
	Th,
	Td,
	Text,
	Box,
	Stack,
	Button,
	Heading,
	Flex,
	TableContainer,
	Link,
	Input,
} from "@chakra-ui/react";
import {
	Pagination,
	usePagination,
	PaginationPage,
	PaginationNext,
	PaginationPrevious,
	PaginationPageGroup,
	PaginationContainer,
	PaginationSeparator,
} from "@ajna/pagination";
import { Select } from "chakra-react-select";
import { FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { getActiveGateways, getFormList, getPaymentEntryData } from "./PaymentApi";
import { __ } from "@wordpress/i18n";
import { useOnType } from "use-ontype";

const PaymentTable = () => {
	const [forms, setForms] = useState([]);
	const [gateways, setGateways] = useState([]);
	const [isGatewaysLoading, setIsGatewaysLoading] = useState(true);
	const [paymentEntry, setPaymentEntry] = useState([]);
	const [paymentLogCount, setPaymentLogCount] = useState(0);
	const [formId, setFormId] = useState(null);
	const [paymentStatus, setPaymentStatus] = useState(null);
	const [gateway, setGateway] = useState(null);
	const [searchQuery, setSearchQuery] = useState();

	const tableHeading = [
		"Entry ID",
		"Form",
		"Customer",
		"Email",
		"Total Amount",
		"Gateway",
		"Status",
		"Transaction Id",
	];

	const mappedOptions = [
		{ label: 5, value: 5 },
		{ label: 10, value: 10 },
		{ label: 25, value: 25 },
		{ label: 50, value: 50 },
	];

	const status = [
		{ label: "Complete", value: "complete" },
		{ label: "Pending", value: "pending" },
		{ label: "Failed", value: "failed" },
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
		total: paymentLogCount,
		limits: {
			outer: outerLimit,
			inner: innerLimit,
		},
		initialState: {
			pageSize: 5,
			isDisabled: false,
			currentPage: 1,
		},
	});

	const handlePageChange = (nextPage) => {
		setCurrentPage(nextPage);
	};

	const handlePageSizeChange = (event) => {
		const pageSize = Number(event.value);
		setPageSize(pageSize);
	};

	const handleLogByForm = (event) => {
		const formId = Number(event ? event.value : 0);
		setCurrentPage(1);
		setFormId(formId);
	};

	const handleLogByStatus = (event) => {
		const status = event ? event.value : "";
		setCurrentPage(1);
		setPaymentStatus(status);
	};

	const handleLogByGateway = (event) => {
		const gw = event ? event.value : "";
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

	useEffect(() => {
		getPaymentEntryData(
			pageSize,
			offset,
			formId,
			paymentStatus,
			searchQuery,
			gateway || "",
		).then((res) => {
			if (res.success) {
				setPaymentEntry(res.payment_log.result);
				setPaymentLogCount(res.payment_log.total_count);
			}
		});
	}, [currentPage, pageSize, offset, formId, paymentStatus, searchQuery, gateway]);

	useEffect(() => {
		getFormList().then((res) => {
			if (res.success) {
				setForms(res.form_list);
			}
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
		<>
			<Box bg="white" p="5" borderRadius={10} minW="md">
				<Box>
					<Heading size={"16px"} fontWeight={"600"} mb={"0"}>
						Payment Log
					</Heading>
					<Text size={"14px"} m={"0"}>
						{__(
							"Track your payment activity by reviewing the detailed payment logs.",
							"everest-forms-pro",
						)}
					</Text>
				</Box>
				<Stack
					direction="column"
					spacing="8"
					mt={{
						base: "10px !important",
						sm: "15px !important",
						md: "2.5rem !important",
						lg: "2.5rem !important",
					}}
				>
					<Stack direction="row" alignItems="center">
						<Flex gap={5}>
							<Select
								placeholder={"Select Form"}
								colorScheme="primary"
								options={forms}
								isClearable
								isSearchable={false}
								onChange={handleLogByForm}
							/>

							<Select
								placeholder={"Select Status"}
								colorScheme="primary"
								options={status}
								isClearable
								isSearchable={false}
								onChange={handleLogByStatus}
							/>
							{(isGatewaysLoading || gateways.length > 0) && (
								<Box minW="170px">
									<Select
										placeholder={__(
											"Select Payment Gateway",
											"everest-forms-pro",
										)}
										colorScheme="primary"
										options={gateways}
										isDisabled={isGatewaysLoading}
										isClearable={!isGatewaysLoading}
										isSearchable={false}
										onChange={handleLogByGateway}
									/>
								</Box>
							)}
							<Input
								w={"sm"}
								placeholder={__(
									"Search...",
									"everest-forms-pro",
								)}
								{...searchLog}
							/>
						</Flex>
					</Stack>
					<TableContainer>
						<Table variant={"simple"} size={"sm"}>
							<Thead>
								<Tr bg={"#F0F7FF"}>
									{tableHeading?.map((heading, index) => (
										<Th key={index}>
											<Text fontSize={"xs"}>
												{__(
													heading,
													"everest-forms-pro",
												)}
											</Text>
										</Th>
									))}
								</Tr>
							</Thead>

							<Tbody>
								{paymentLogCount === 0 && (
									<Flex justifyContent={"center"}>
										<Text>
											{__(
												"No Data Found..",
												"everest-forms-pro",
											)}
										</Text>
									</Flex>
								)}
								{paymentEntry?.map((entry, index) => (
									<Tr key={index}>
										<Td>
											<Text>
												{__(
													entry.submission_id,
													"everest-forms-pro",
												)}
											</Text>
										</Td>

										<Td>
											<Text>
												{__(
													entry.form,
													"everest-forms-pro",
												)}
											</Text>
										</Td>

										<Td>
											<Text>
												{__(
													entry.customer,
													"everest-forms-pro",
												)}
											</Text>
										</Td>

										<Td>
											<Text>
												{__(
													entry.total_amount,
													"everest-forms-pro",
												) +
													" " +
													entry.payment_currency}
											</Text>
										</Td>

										<Td>
											<Text>
												{__(
													entry.payment_gateway,
													"everest-forms-pro",
												)}
											</Text>
										</Td>

										<Td>
											{(() => {
												const normalizedStatus = String(
													entry.status || "",
												).toLowerCase();
												const isCancelledStatus = [
													"cancelled",
													"canceled",
													"cancled",
													"cancel",
												].includes(normalizedStatus);
												const statusColor =
													normalizedStatus === "failed"
														? "#DC3545"
														: normalizedStatus ===
																	"complete" ||
															  normalizedStatus ===
																	"completed"
															? "#28A745"
															: isCancelledStatus
																? "#7e7d77"
																: "#FFC700";

												return (
													<Text
														color={statusColor}
														fontWeight={"500"}
														display={
															isCancelledStatus
																? "inline-block"
																: "inline"
														}
														border={
															isCancelledStatus
																? "1px solid #7e7d77"
																: "none"
														}
														borderRadius={
															isCancelledStatus
																? "20px"
																: "0"
														}
														px={
															isCancelledStatus
																? "10px"
																: "0"
														}
														py={
															isCancelledStatus
																? "2px"
																: "0"
														}
													>
														{__(
															entry.status,
															"everest-forms-pro",
														)}
													</Text>
												);
											})()}
										</Td>
										<Td>
											<Link
												href={entry.receipt_url}
												isExternal
											>
												{entry.payment_transaction}
											</Link>
										</Td>
									</Tr>
								))}
							</Tbody>
						</Table>
					</TableContainer>
				</Stack>

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
								defaultValue={mappedOptions[0]}
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
									{pages?.map((page) => (
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
			</Box>
		</>
	);
};

export default PaymentTable;
