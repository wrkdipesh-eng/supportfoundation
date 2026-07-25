import React from "react";
import { ChakraProvider } from "@chakra-ui/react";
import theme from '../../../../../everest-forms/src/dashboard/Theme/Theme';
import PaymentTable from "./components/PaymentTable";

const App = () => {
	return (
		<ChakraProvider theme={theme}>
			<PaymentTable/>
		</ChakraProvider>
	);
};

export default App;
