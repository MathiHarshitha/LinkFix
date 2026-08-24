import { __ } from '@wordpress/i18n';
import Nav from './components/Nav';
import useHashRoute from './hooks/useHashRoute';
import Dashboard from './pages/Dashboard';
import Settings from './pages/Settings';
import Rules from './pages/Rules';
import Exclusions from './pages/Exclusions';
import Scanner from './pages/Scanner';
import Statistics from './pages/Statistics';
import Tools from './pages/Tools';
import Documentation from './pages/Documentation';

const CORE_PAGES = {
	dashboard: Dashboard,
	settings: Settings,
	rules: Rules,
	exclusions: Exclusions,
	scanner: Scanner,
	statistics: Statistics,
	tools: Tools,
	docs: Documentation,
};

export default function App() {
	const initialRoute = window.elmAdminData?.initialRoute || 'dashboard';
	const [ route, navigate ] = useHashRoute( initialRoute );

	/**
	 * Pro (and any extension) registers additional page components via
	 * window.elmAdmin.registerPage() before this component first renders.
	 */
	const extraPages = window.elmAdmin?.pages || {};
	const pages = { ...CORE_PAGES, ...extraPages };

	const ActivePage = pages[ route ] || Dashboard;

	return (
		<div className="elm-app">
			<Nav route={ route } onNavigate={ navigate } />
			<main
				className="elm-main"
				aria-label={ __(
					'External Link Manager settings',
					'external-link-manager'
				) }
			>
				<ActivePage />
			</main>
		</div>
	);
}
