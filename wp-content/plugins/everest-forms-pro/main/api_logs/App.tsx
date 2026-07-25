import React from "react";
import { ChakraProvider } from "@chakra-ui/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MainLayout from "./components/MainLayout";

const App: React.FC = () => {
	const queryClient = new QueryClient();
	return (
		<ChakraProvider>
			<QueryClientProvider client={queryClient}>
				<MainLayout />
			</QueryClientProvider>
		</ChakraProvider>
	);
};

export default App;
