import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createRoot } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import ProAnalytics from './App';
import './main.scss';

const queryClient = new QueryClient({
	defaultOptions: {
		queries: {
			retry: 1,
			refetchOnWindowFocus: false,
			staleTime: 5 * 60 * 1000,
		},
	},
});

const ProAnalyticsWithProvider = () => {
	return (
		<QueryClientProvider client={queryClient}>
			<ProAnalytics />
		</QueryClientProvider>
	);
};

addFilter(
	'everest-forms-analytics',
	'everest-forms-pro',
	() => ProAnalyticsWithProvider,
);

const analyticsRoot = document.getElementById('evf-analytics-root');
if (analyticsRoot) {
	const root = createRoot(analyticsRoot);
	root.render(
		<QueryClientProvider client={queryClient}>
			<ProAnalytics />
		</QueryClientProvider>,
	);
}
