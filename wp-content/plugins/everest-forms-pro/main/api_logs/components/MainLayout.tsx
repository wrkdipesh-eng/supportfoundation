import { Box, Divider, Heading, Stack, Text } from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import React from "react";
import ApiLogsTable from "./ApiLogsTable";

const MainLayout = () => {
	return (
		<Stack
			direction="column"
			gap="16px"
			padding="20px 20px 40px 20px"
			bg="white"
		>
			<Stack padding="10px 0px 10px 0px" gap="8px" direction="column">
				<Heading
					fontWeight="600"
					size="20px"
					lineHeight="8px"
					color="#222222"
				>
					{__("Api Logs", "everest-forms-pro")}
				</Heading>
				<Text
					fontWeight="400"
					size="14px"
					lineHeight="8px"
					color="#454545"
				>
					{__(
						"Get external CRM and API call logs here. Track api logs activity. (Last 2 months logs)",
						"everest-forms-pro",
					)}
				</Text>
				<Divider border="1px solid #DFDFDF" />
			</Stack>
			<ApiLogsTable />
		</Stack>
	);
};

export default MainLayout;
