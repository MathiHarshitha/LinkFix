import { __ } from '@wordpress/i18n';
import {
	link,
	cog,
	listView,
	filter,
	search,
	chartBar,
	tool,
	info,
} from '@wordpress/icons';
import { Icon } from '@wordpress/components';

const ICONS = {
	dashboard: chartBar,
	settings: cog,
	rules: listView,
	exclusions: filter,
	scanner: search,
	statistics: chartBar,
	tools: tool,
	docs: info,
};

const CORE_ITEMS = [
	{ id: 'dashboard', label: __( 'Dashboard', 'external-link-manager' ) },
	{ id: 'settings', label: __( 'Settings', 'external-link-manager' ) },
	{ id: 'rules', label: __( 'Rules', 'external-link-manager' ) },
	{ id: 'exclusions', label: __( 'Exclusions', 'external-link-manager' ) },
	{ id: 'scanner', label: __( 'Scanner', 'external-link-manager' ) },
	{ id: 'statistics', label: __( 'Statistics', 'external-link-manager' ) },
	{ id: 'tools', label: __( 'Tools', 'external-link-manager' ) },
	{ id: 'docs', label: __( 'Documentation', 'external-link-manager' ) },
];

export default function Nav( { route, onNavigate } ) {
	// Extensions (Pro) call window.elmAdmin.registerNavItem( { id, label, icon } )
	// before the app mounts — icon is an already-resolved icon element/name.
	const extraItems = window.elmAdmin?.navItems || [];
	const items = [ ...CORE_ITEMS, ...extraItems ];

	return (
		<nav
			className="elm-nav"
			aria-label={ __(
				'External Link Manager',
				'external-link-manager'
			) }
		>
			<div className="elm-nav__brand">
				<Icon icon={ link } size={ 22 } />
				<span>{ __( 'Link Manager', 'external-link-manager' ) }</span>
			</div>
			<ul className="elm-nav__list">
				{ items.map( ( item ) => (
					<li key={ item.id }>
						<button
							type="button"
							className={
								'elm-nav__item' +
								( route === item.id ? ' is-active' : '' )
							}
							onClick={ () => onNavigate( item.id ) }
						>
							<Icon
								icon={ item.icon || ICONS[ item.id ] || info }
								size={ 18 }
							/>
							<span>{ item.label }</span>
						</button>
					</li>
				) ) }
			</ul>
		</nav>
	);
}
