import { ChakraProvider } from '@chakra-ui/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import theme from '../../../../../everest-forms/src/dashboard/Theme/Theme';
import MainLayout from './components/MainLayout';

const App: React.FC = () => {
	const queryClient = new QueryClient();
	return (
		<ChakraProvider theme={theme}>
			<QueryClientProvider client={queryClient}>
				<MainLayout />
			</QueryClientProvider>
		</ChakraProvider>
	);
};

export default App;
