/**
 * Thin wrapper around @wordpress/api-fetch, pointed at Free's own
 * elm/v1 REST namespace and authenticated with the site's REST nonce.
 * No settings validation happens here — the server is authoritative;
 * this module only shapes requests/responses for the UI.
 *
 * Builds the full URL directly (restUrl already includes the elm/v1
 * namespace) rather than relying on api-fetch's root-URL middleware,
 * whose path-merging behaves differently depending on the site's
 * permalink structure.
 */
import apiFetch from '@wordpress/api-fetch';

const { restUrl, restNonce } = window.elmAdminData || {};

apiFetch.use( apiFetch.createNonceMiddleware( restNonce ) );

const request = ( path, options = {} ) =>
	apiFetch( { url: `${ restUrl }${ path }`, ...options } );

export const api = {
	getSettings: () => request( '/settings' ),
	updateSettings: ( data ) =>
		request( '/settings', { method: 'POST', data } ),
	getPostTypes: () => request( '/settings/post-types' ),

	getRules: () => request( '/rules' ),
	saveRules: ( rules ) =>
		request( '/rules', { method: 'POST', data: { rules } } ),

	getExclusions: () => request( '/exclusions' ),
	saveExclusions: ( exclusions ) =>
		request( '/exclusions', { method: 'POST', data: { exclusions } } ),

	getScannerState: () => request( '/scanner/state' ),
	startScan: ( postTypes ) =>
		request( '/scanner/start', {
			method: 'POST',
			data: { post_types: postTypes },
		} ),
	runScanBatch: () => request( '/scanner/batch', { method: 'POST' } ),
	pauseScan: () => request( '/scanner/pause', { method: 'POST' } ),
	resumeScan: () => request( '/scanner/resume', { method: 'POST' } ),
	cancelScan: () => request( '/scanner/cancel', { method: 'POST' } ),

	getBulkApplyState: () => request( '/bulk-apply/state' ),
	startBulkApply: ( postTypes ) =>
		request( '/bulk-apply/start', {
			method: 'POST',
			data: { post_types: postTypes },
		} ),
	runBulkApplyBatch: () => request( '/bulk-apply/batch', { method: 'POST' } ),
	resumeBulkApply: () => request( '/bulk-apply/resume', { method: 'POST' } ),
	cancelBulkApply: () => request( '/bulk-apply/cancel', { method: 'POST' } ),

	getStatistics: () => request( '/statistics' ),

	getSystemInfo: () => request( '/system/info' ),
	exportData: () => request( '/system/export' ),
	importData: ( payload ) =>
		request( '/system/import', { method: 'POST', data: payload } ),
	clearIndex: () => request( '/system/clear-index', { method: 'POST' } ),
	getUninstallPreference: () => request( '/system/uninstall-preference' ),
	setUninstallPreference: ( removeDataOnUninstall ) =>
		request( '/system/uninstall-preference', {
			method: 'POST',
			data: { remove_data_on_uninstall: removeDataOnUninstall },
		} ),
};

export default api;
