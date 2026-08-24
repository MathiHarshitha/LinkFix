import { createRoot } from '@wordpress/element';
import App from './admin/App';
import './admin/style.scss';

/**
 * Extension registration API. External Link Manager Pro (or any other
 * extension) enqueues its own script as a dependency of this one
 * ('elm-admin'), so it executes right after this file and can call
 * these before the app mounts. See docs/hooks.md.
 */
window.elmAdmin = window.elmAdmin || { pages: {}, navItems: [] };
window.elmAdmin.registerPage = ( routeId, Component ) => {
	window.elmAdmin.pages[ routeId ] = Component;
};
window.elmAdmin.registerNavItem = ( item ) => {
	window.elmAdmin.navItems.push( item );
};

function mount() {
	const root = document.getElementById( 'elm-admin-root' );

	if ( ! root ) {
		return;
	}

	createRoot( root ).render( <App /> );
}

// Deferred one tick (macrotask) so any extension script that loaded as
// a dependency of this one has already run its top-level registration
// calls by the time we render — synchronous script execution across
// script tags always completes before a setTimeout(fn, 0) callback runs.
function deferredMount() {
	setTimeout( mount, 0 );
}

if (
	'complete' === document.readyState ||
	'interactive' === document.readyState
) {
	deferredMount();
} else {
	document.addEventListener( 'DOMContentLoaded', deferredMount );
}
