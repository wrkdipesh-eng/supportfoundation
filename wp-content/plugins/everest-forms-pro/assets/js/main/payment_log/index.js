import React from "react";
import ReactDOM from "react-dom/client";
import App from './App';

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById("everest-forms-payment-log");

	if (!container) return;

	const root = ReactDOM.createRoot(container)

	root.render(<App />);
});
