import React from "react";
import { ChakraProvider } from "@chakra-ui/react";
import PaymentTable from "./components/PaymentTable";

const App = () => {
	return (
		<ChakraProvider>
			<PaymentTable/>
		</ChakraProvider>
	);
};

export default App;
