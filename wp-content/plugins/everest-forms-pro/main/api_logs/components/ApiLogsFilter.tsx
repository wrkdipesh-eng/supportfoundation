import React, { useState } from "react";
import { Box, Button, Input, Stack, Text } from "@chakra-ui/react";
import { Controller, useForm } from "react-hook-form";
import { _x, __ } from "@wordpress/i18n";
import { Select } from "chakra-react-select";
import { useOnType } from "use-ontype";
import { DateRangePicker } from "react-date-range";
import "react-date-range/dist/styles.css";
import "react-date-range/dist/theme/default.css";
import { format, subDays } from "date-fns";

const ApiLogsFilter: React.FC = ({
	searchQuery,
	setSearchQuery,
	handleBulkAction,
}: any) => {
	const [daterange, setDateRange] = useState([
		{
			startDate: subDays(new Date(), -30),
			endDate: new Date(),
			key: "selection",
		},
	]);
	const [actionType, setActionType] = useState("");
	const [showDateRangePicker, setShowDateRangePicker] = useState(false);
	const { handleSubmit, control, setValue } = useForm();

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
		return `${format(startDate, "MM/dd/yyyy")} - ${format(endDate, "MM/dd/yyyy")}`;
	};

	return (
		<form>
			<Stack
				w="100%"
				minHeight="62px"
				padding="0px 0px 24px 0px"
				gap="16px"
				direction="row"
				flexWrap="wrap"
			>
				<Box flex="1" minW="150px">
					<Input
						placeholder={__("Search by item", "everest-forms-pro")}
						{...onSearchItem}
					/>
				</Box>

				<Box flex="1" minW="200px">
					<Input
						isReadOnly
						bg="white"
						color="black"
						_placeholder={{ color: "gray.500" }}
						_focus={{ bg: "white", borderColor: "blue.500" }}
						_hover={{ bg: "white" }}
						placeholder={__(
							"Select Date Range",
							"everest-forms-pro",
						)}
						value={getFormattedDateRange()}
						onClick={() => setShowDateRangePicker(true)}
					/>
					{showDateRangePicker && (
						<Box position="absolute" zIndex="1">
							<DateRangePicker
								onChange={(item) => {
									setDateRange([item.selection]);
									setSearchQuery({
										...searchQuery,
										searchByDates: item.selection,
									});
									setShowDateRangePicker(false);
									setValue("searchByDate", item.selection);
								}}
								showSelectionPreview={true}
								moveRangeOnFirstSelection={false}
								months={2}
								ranges={daterange}
								direction="horizontal"
								preventSnapRefocus={true}
								calendarFocus="backwards"
							/>
						</Box>
					)}
				</Box>

				<Controller
					name="searchByStatus"
					control={control}
					render={({ field: { onChange: onChangeValue, value } }) => (
						<Box minW="170px">
							<Select
								onChange={(...args: any[]) => {
									onChangeValue(...args);
									handleSubmit(onChange)();
								}}
								value={value}
								options={[
									{
										label: _x("OK", "everest-forms-pro"),
										value: 200,
									},
									{
										label: _x(
											"Created",
											"everest-forms-pro",
										),
										value: 201,
									},
									{
										label: _x(
											"Accepted",
											"everest-forms-pro",
										),
										value: 202,
									},
									{
										label: _x(
											"No Content",
											"everest-forms-pro",
										),
										value: 204,
									},
									{
										label: _x(
											"Moved Permanently",
											"everest-forms-pro",
										),
										value: 301,
									},
									{
										label: _x("Found", "everest-forms-pro"),
										value: 302,
									},
									{
										label: _x(
											"Not Modified",
											"everest-forms-pro",
										),
										value: 304,
									},
									{
										label: _x(
											"Bad Request",
											"everest-forms-pro",
										),
										value: 400,
									},
									{
										label: _x(
											"Unauthorized",
											"everest-forms-pro",
										),
										value: 401,
									},
									{
										label: _x(
											"Forbidden",
											"everest-forms-pro",
										),
										value: 403,
									},
									{
										label: _x(
											"Not Found",
											"everest-forms-pro",
										),
										value: 404,
									},
									{
										label: _x(
											"Method Not Allowed",
											"everest-forms-pro",
										),
										value: 405,
									},
									{
										label: _x(
											"Conflict",
											"everest-forms-pro",
										),
										value: 409,
									},
									{
										label: _x("Gone", "everest-forms-pro"),
										value: 410,
									},
									{
										label: _x(
											"Internal Server Error",
											"everest-forms-pro",
										),
										value: 500,
									},
									{
										label: _x(
											"Bad Gateway",
											"everest-forms-pro",
										),
										value: 502,
									},
									{
										label: _x(
											"Service Unavailable",
											"everest-forms-pro",
										),
										value: 503,
									},
									{
										label: _x(
											"Gateway Timeout",
											"everest-forms-pro",
										),
										value: 504,
									},
								]}
								placeholder={_x(
									"Search by Status",
									"everest-forms-pro",
								)}
								isClearable
								isSearchable={false}
							/>
						</Box>
					)}
				/>

				<Stack direction="row" gap="16px" flex="1" minW="280px">
					<Box flex="1" minW="150px">
						<Select
							placeholder={_x(
								"Bulk Actions",
								"everest-forms-pro",
							)}
							options={[
								{
									label: __("Delete", "everest-forms-pro"),
									value: "delete",
								},
							]}
							onChange={(option) => setActionType(option?.value)}
							isClearable
							isSearchable={false}
						/>
					</Box>

					<Button
						minW="64px"
						minH="36px"
						borderRadius="3px"
						border="1px solid #475BB2"
						padding="8px 14px 8px 14px"
						type="button"
						bg="#F6F7F7"
						onClick={() => handleBulkAction({ action: actionType })}
					>
						<Text fontWeight="500" size="13px" lineHeight="19.5px">
							{__("Apply", "everest-forms-pro")}
						</Text>
					</Button>
				</Stack>
			</Stack>
		</form>
	);
};

export default ApiLogsFilter;
