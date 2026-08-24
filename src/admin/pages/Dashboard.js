import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Spinner,
	Notice,
	Card,
	CardBody,
	CardHeader,
} from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import StatCard from '../components/StatCard';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

const REL_LABELS = {
	noopener: 'noopener',
	noreferrer: 'noreferrer',
	nofollow: 'nofollow',
	sponsored: 'sponsored',
	ugc: 'ugc',
	new_tab: __( 'New tab', 'external-link-manager' ),
};

function ScanStatusLine( { lastScan } ) {
	if ( ! lastScan || 'idle' === lastScan.status ) {
		return (
			<p>
				{ __(
					'No scan has run yet. Head to the Scanner tab to run your first scan.',
					'external-link-manager'
				) }
			</p>
		);
	}

	const statusLabels = {
		running: __( 'Scan in progress…', 'external-link-manager' ),
		paused: __( 'Scan paused', 'external-link-manager' ),
		completed: __( 'Last scan completed', 'external-link-manager' ),
		cancelled: __( 'Last scan was cancelled', 'external-link-manager' ),
		interrupted: __( 'Last scan was interrupted', 'external-link-manager' ),
	};

	return (
		<p>
			<strong>
				{ statusLabels[ lastScan.status ] || lastScan.status }
			</strong>
			{ ' — ' }
			{ sprintf(
				/* translators: 1: processed items, 2: total items, 3: links found */
				__(
					'%1$d of %2$d items scanned, %3$d external links found.',
					'external-link-manager'
				),
				lastScan.processed_items || 0,
				lastScan.total_items || 0,
				lastScan.links_found || 0
			) }
		</p>
	);
}

export default function Dashboard() {
	const [ stats, setStats ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		api.getStatistics()
			.then( ( data ) => setStats( data ) )
			.catch( ( err ) => setError( err.message || String( err ) ) )
			.finally( () => setLoading( false ) );
	}, [] );

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Dashboard', 'external-link-manager' ) }
				description={ __(
					'A local, privacy-friendly snapshot of the external links on your site.',
					'external-link-manager'
				) }
			/>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ loading && <Spinner /> }

			{ stats && (
				<>
					<div className="elm-stat-grid">
						<StatCard
							label={ __(
								'External links',
								'external-link-manager'
							) }
							value={ stats.total_external_links }
						/>
						<StatCard
							label={ __(
								'Unique domains',
								'external-link-manager'
							) }
							value={ stats.unique_domains }
						/>
						<StatCard
							label={ __(
								'Content with external links',
								'external-link-manager'
							) }
							value={ stats.content_with_links }
						/>
					</div>

					<Card className="elm-card">
						<CardHeader>
							<h2>
								{ __( 'Scan status', 'external-link-manager' ) }
							</h2>
						</CardHeader>
						<CardBody>
							<ScanStatusLine lastScan={ stats.last_scan } />
						</CardBody>
					</Card>

					<div className="elm-two-col">
						<Card className="elm-card">
							<CardHeader>
								<h2>
									{ __(
										'Top domains',
										'external-link-manager'
									) }
								</h2>
							</CardHeader>
							<CardBody>
								{ stats.top_domains &&
								stats.top_domains.length > 0 ? (
									<table className="elm-table">
										<thead>
											<tr>
												<th>
													{ __(
														'Domain',
														'external-link-manager'
													) }
												</th>
												<th>
													{ __(
														'Links',
														'external-link-manager'
													) }
												</th>
											</tr>
										</thead>
										<tbody>
											{ stats.top_domains.map( ( d ) => (
												<tr key={ d.domain }>
													<td>{ d.domain }</td>
													<td>{ d.count }</td>
												</tr>
											) ) }
										</tbody>
									</table>
								) : (
									<p>
										{ __(
											'No data yet — run a scan to populate this.',
											'external-link-manager'
										) }
									</p>
								) }
							</CardBody>
						</Card>

						<Card className="elm-card">
							<CardHeader>
								<h2>
									{ __(
										'Rel attribute summary',
										'external-link-manager'
									) }
								</h2>
							</CardHeader>
							<CardBody>
								{ stats.rel_distribution && (
									<ul className="elm-attr-list">
										{ Object.keys( REL_LABELS ).map(
											( key ) => (
												<li key={ key }>
													<span>
														{ REL_LABELS[ key ] }
													</span>
													<strong>
														{ stats
															.rel_distribution[
															key
														] || 0 }
													</strong>
												</li>
											)
										) }
									</ul>
								) }
							</CardBody>
						</Card>
					</div>
				</>
			) }

			<UpgradeCard />
		</div>
	);
}
