interface Window {
	__EVF_ANALYTICS__: {
		install_date: string;
		memberships: Array<{
			id: number;
			name: string;
		}>;
		currency: string;
		data_sets: {
			summary: {
				slug: string;
				label: string;
			}[];
			visualization: {
				slug: string;
				label: string;
				type?: "pie" | "list";
				metrics: {
					slug: string;
					label: string;
				}[];
				legacy?: boolean;
			}[];
		};
	};
}
