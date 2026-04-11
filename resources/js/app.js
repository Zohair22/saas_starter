import './bootstrap';
import './echo';
import { createInertiaApp } from '@inertiajs/react';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { ToastProvider } from './Components/ToastProvider';
import { joinProjectRealtime, leaveProjectRealtime } from './realtime/projectRealtime';

window.ProjectRealtime = {
	joinProjectRealtime,
	leaveProjectRealtime,
};

createInertiaApp({
	title: (title) => (title ? `${title} | SaaS Starter` : 'SaaS Starter'),
	resolve: (name) => {
		const pages = import.meta.glob('./Pages/**/*.jsx');

		return pages[`./Pages/${name}.jsx`]();
	},
	setup({ el, App, props }) {
		createRoot(el).render(createElement(ToastProvider, null, createElement(App, props)));
	},
	progress: {
		color: '#1f2937',
		showSpinner: false,
	},
});
