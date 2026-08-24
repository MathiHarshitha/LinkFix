/**
 * Minimal hash-based route state. Free's admin UI lives across several
 * WP submenu pages that all render the same root; this hook just keeps
 * a `#/tab` fragment in sync with the active tab so the browser back
 * button and deep links behave, without pulling in a router library.
 */
import { useEffect, useState, useCallback } from '@wordpress/element';

const getHashRoute = ( fallback ) => {
	const hash = window.location.hash.replace( /^#\/?/, '' );
	return hash || fallback;
};

export default function useHashRoute( fallback = 'dashboard' ) {
	const [ route, setRoute ] = useState( () => getHashRoute( fallback ) );

	useEffect( () => {
		const onHashChange = () => setRoute( getHashRoute( fallback ) );
		window.addEventListener( 'hashchange', onHashChange );
		return () => window.removeEventListener( 'hashchange', onHashChange );
	}, [ fallback ] );

	const navigate = useCallback( ( nextRoute ) => {
		window.location.hash = `/${ nextRoute }`;
		setRoute( nextRoute );
	}, [] );

	return [ route, navigate ];
}
