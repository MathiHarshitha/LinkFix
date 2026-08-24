import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Notice,
} from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import StatCard from '../components/StatCard';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

function Bar( { label, value, max } ) {
	const pct = max > 0 ? Math.round( ( value / max ) * 100 ) : 0;

	return (
		<div className="elm-bar-row">
			<span className="elm-bar-row__label">{ label }</span>
			<div className="elm-bar-row__track">
				<div
					className="elm-bar-row__fill"
					style={ { width: `${ pct }%` } }
				/>
			</div>
			<span className="elm-bar-row__value">{ value }</span>
		</div>
	);
}

export default function Statistics() {
	const [ stats, setStats ] = useState( null );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		api.getStatistics()
			.then( setStats )
			.catch( ( err ) => setError( err.message || String( err ) ) );
	}, [] );

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	if ( ! stats ) {
		return <Spinner />;
	}

	const relEntries = Object.entries( stats.rel_distribution || {} );
	const relMax = Math.max( 1, ...relEntries.map( ( [ , v ] ) => v ) );
	const domainMax = Math.max(
		1,
		...( stats.top_domains || [] ).map( ( d ) => d.count )
	);

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Statistics', 'external-link-manager' ) }
				description={ __(
					'Computed entirely from your own content scans — nothing is sent anywhere.',
					'external-link-manager'
				) }
			/>

			<div className="elm-stat-grid">
				<StatCard
					label={ __(
						'Total external links',
						'external-link-manager'
					) }
					value={ stats.total_external_links }
				/>
				<StatCard
					label={ __( 'Unique domains', 'external-link-manager' ) }
					value={ stats.unique_domains }
				/>
				<StatCard
					label={ __(
						'Content items with links',
						'external-link-manager'
					) }
					value={ stats.content_with_links }
				/>
			</div>

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __(
							'Top 20 external domains',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					{ ( stats.top_domains || [] ).length === 0 && (
						<p>{ __( 'No data yet.', 'external-link-manager' ) }</p>
					) }
					{ ( stats.top_domains || [] ).map( ( d ) => (
						<Bar
							key={ d.domain }
							label={ d.domain }
							value={ d.count }
							max={ domainMax }
						/>
					) ) }
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __(
							'Rel attribute distribution',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					{ relEntries.map( ( [ key, value ] ) => (
						<Bar
							key={ key }
							label={ key }
							value={ value }
							max={ relMax }
						/>
					) ) }
				</CardBody>
			</Card>

			<UpgradeCard />
		</div>
	);
}
