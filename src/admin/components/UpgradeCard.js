import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Small, tasteful upsell — shown once per page, never a modal/popup,
 * and never in the way of any Free functionality.
 */
export default function UpgradeCard() {
	const { isPro, upgradeUrl } = window.elmAdminData || {};

	if ( isPro ) {
		return null;
	}

	return (
		<div className="elm-upgrade-card">
			<h3>
				{ __( 'External Link Manager Pro', 'external-link-manager' ) }
			</h3>
			<p>
				{ __(
					'Monitor broken links, redirects, advanced rules, scheduled scans, and detailed reports.',
					'external-link-manager'
				) }
			</p>
			<Button
				variant="secondary"
				href={ upgradeUrl }
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Explore Pro', 'external-link-manager' ) }
			</Button>
		</div>
	);
}
