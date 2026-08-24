import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	CheckboxControl,
	Notice,
} from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

const STATUS_LABELS = {
	idle: __( 'Idle', 'external-link-manager' ),
	running: __( 'Running', 'external-link-manager' ),
	paused: __( 'Paused', 'external-link-manager' ),
	completed: __( 'Completed', 'external-link-manager' ),
	cancelled: __( 'Cancelled', 'external-link-manager' ),
	interrupted: __( 'Interrupted', 'external-link-manager' ),
};

function ProgressBar( { processed, total } ) {
	const pct =
		total > 0
			? Math.min( 100, Math.round( ( processed / total ) * 100 ) )
			: 0;

	return (
		<div className="elm-progress">
			<div className="elm-progress__track">
				<div
					className="elm-progress__fill"
					style={ { width: `${ pct }%` } }
				/>
			</div>
			<span className="elm-progress__label">{ pct }%</span>
		</div>
	);
}

function useJobRunner( { getState, runBatch } ) {
	const [ state, setState ] = useState( null );
	const runningRef = useRef( false );

	const refresh = () => getState().then( setState );

	// getState/runBatch are stable API-module functions (not component
	// state), so omitting them from the deps arrays is intentional: this
	// effect should run once on mount, and the poll loop below should
	// only re-run when `state` itself changes.
	useEffect( () => {
		refresh();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		if ( ! state || 'running' !== state.status || runningRef.current ) {
			return;
		}

		runningRef.current = true;

		const tick = () => {
			runBatch().then( ( next ) => {
				setState( next );
				runningRef.current = false;
			} );
		};

		const id = setTimeout( tick, 150 );
		return () => clearTimeout( id );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ state ] );

	return [ state, setState, refresh ];
}

function ScannerJob() {
	const [ postTypes, setPostTypes ] = useState( {} );
	const [ selectedTypes, setSelectedTypes ] = useState( [] );
	const [ state, setState, refresh ] = useJobRunner( {
		getState: api.getScannerState,
		runBatch: api.runScanBatch,
	} );

	useEffect( () => {
		api.getPostTypes().then( ( types ) => {
			setPostTypes( types );
			setSelectedTypes( Object.keys( types ) );
		} );
	}, [] );

	const toggleType = ( slug, checked ) => {
		setSelectedTypes(
			checked
				? [ ...selectedTypes, slug ]
				: selectedTypes.filter( ( t ) => t !== slug )
		);
	};

	const start = () => api.startScan( selectedTypes ).then( setState );
	const pause = () => api.pauseScan().then( setState );
	const resume = () => api.resumeScan().then( setState );
	const cancel = () => api.cancelScan().then( setState );

	const isRunning = state && 'running' === state.status;
	const isPaused =
		state && [ 'paused', 'interrupted' ].includes( state.status );
	const isActive = isRunning || isPaused;

	return (
		<Card className="elm-card">
			<CardHeader>
				<h2>
					{ __( 'Scan for external links', 'external-link-manager' ) }
				</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'Discovers external links in existing content and indexes them for statistics. This never modifies your content.',
						'external-link-manager'
					) }
				</p>

				{ ! isActive && (
					<div className="elm-scope-picker">
						{ Object.keys( postTypes ).map( ( slug ) => (
							<CheckboxControl
								key={ slug }
								label={ postTypes[ slug ] }
								checked={ selectedTypes.includes( slug ) }
								onChange={ ( checked ) =>
									toggleType( slug, checked )
								}
								__nextHasNoMarginBottom
							/>
						) ) }
					</div>
				) }

				{ state && state.status !== 'idle' && (
					<div className="elm-job-status">
						<p>
							{ sprintf(
								/* translators: %s: status label */
								__( 'Status: %s', 'external-link-manager' ),
								STATUS_LABELS[ state.status ] || state.status
							) }
						</p>
						<ProgressBar
							processed={ state.processed_items }
							total={ state.total_items }
						/>
						<p>
							{ sprintf(
								/* translators: 1: processed, 2: total, 3: links found */
								__(
									'%1$d / %2$d items — %3$d external links found so far',
									'external-link-manager'
								),
								state.processed_items,
								state.total_items,
								state.links_found
							) }
						</p>
						{ state.errors && state.errors.length > 0 && (
							<Notice status="warning" isDismissible={ false }>
								{ sprintf(
									/* translators: %d: error count */
									__(
										'%d item(s) could not be scanned.',
										'external-link-manager'
									),
									state.errors.length
								) }
							</Notice>
						) }
					</div>
				) }

				<div className="elm-job-actions">
					{ ! isActive && (
						<Button
							variant="primary"
							onClick={ start }
							disabled={ selectedTypes.length === 0 }
						>
							{ __( 'Start scan', 'external-link-manager' ) }
						</Button>
					) }
					{ isRunning && (
						<Button variant="secondary" onClick={ pause }>
							{ __( 'Pause', 'external-link-manager' ) }
						</Button>
					) }
					{ isPaused && (
						<Button variant="secondary" onClick={ resume }>
							{ __( 'Resume', 'external-link-manager' ) }
						</Button>
					) }
					{ isActive && (
						<Button
							variant="tertiary"
							isDestructive
							onClick={ cancel }
						>
							{ __( 'Cancel', 'external-link-manager' ) }
						</Button>
					) }
					{ ! isActive && state && state.status !== 'idle' && (
						<Button variant="link" onClick={ refresh }>
							{ __( 'Refresh', 'external-link-manager' ) }
						</Button>
					) }
				</div>
			</CardBody>
		</Card>
	);
}

function BulkApplyJob() {
	const [ postTypes, setPostTypes ] = useState( {} );
	const [ selectedTypes, setSelectedTypes ] = useState( [] );
	const [ confirming, setConfirming ] = useState( false );
	const [ state, setState ] = useJobRunner( {
		getState: api.getBulkApplyState,
		runBatch: api.runBulkApplyBatch,
	} );

	useEffect( () => {
		api.getPostTypes().then( ( types ) => {
			setPostTypes( types );
			setSelectedTypes( Object.keys( types ) );
		} );
	}, [] );

	const toggleType = ( slug, checked ) => {
		setSelectedTypes(
			checked
				? [ ...selectedTypes, slug ]
				: selectedTypes.filter( ( t ) => t !== slug )
		);
	};

	const start = () => {
		setConfirming( false );
		api.startBulkApply( selectedTypes ).then( setState );
	};
	const cancel = () => api.cancelBulkApply().then( setState );

	const isRunning = state && 'running' === state.status;

	return (
		<Card className="elm-card">
			<CardHeader>
				<h2>
					{ __(
						'Apply settings to existing content',
						'external-link-manager'
					) }
				</h2>
			</CardHeader>
			<CardBody>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'This rewrites <a> attributes directly into your stored content, in batches. It preserves any rel values it did not add.',
						'external-link-manager'
					) }
				</Notice>

				{ ! isRunning && ! confirming && (
					<>
						<div className="elm-scope-picker">
							{ Object.keys( postTypes ).map( ( slug ) => (
								<CheckboxControl
									key={ slug }
									label={ postTypes[ slug ] }
									checked={ selectedTypes.includes( slug ) }
									onChange={ ( checked ) =>
										toggleType( slug, checked )
									}
									__nextHasNoMarginBottom
								/>
							) ) }
						</div>
						<Button
							variant="primary"
							onClick={ () => setConfirming( true ) }
							disabled={ selectedTypes.length === 0 }
						>
							{ __(
								'Apply to existing content…',
								'external-link-manager'
							) }
						</Button>
					</>
				) }

				{ confirming && (
					<div className="elm-confirm">
						<p>
							{ __(
								'This will modify stored content for the selected content types. Continue?',
								'external-link-manager'
							) }
						</p>
						<Button
							variant="primary"
							isDestructive
							onClick={ start }
						>
							{ __( 'Yes, apply now', 'external-link-manager' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => setConfirming( false ) }
						>
							{ __( 'Cancel', 'external-link-manager' ) }
						</Button>
					</div>
				) }

				{ state && state.status !== 'idle' && (
					<div className="elm-job-status">
						<p>{ STATUS_LABELS[ state.status ] || state.status }</p>
						<ProgressBar
							processed={ state.processed_items }
							total={ state.total_items }
						/>
						<p>
							{ sprintf(
								/* translators: 1: items changed, 2: links affected */
								__(
									'%1$d item(s) changed, %2$d link(s) affected',
									'external-link-manager'
								),
								state.items_changed,
								state.links_affected
							) }
						</p>
					</div>
				) }

				{ isRunning && (
					<Button variant="tertiary" isDestructive onClick={ cancel }>
						{ __( 'Cancel', 'external-link-manager' ) }
					</Button>
				) }
			</CardBody>
		</Card>
	);
}

export default function Scanner() {
	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Scanner', 'external-link-manager' ) }
				description={ __(
					'Batch-scan your content for external links, or apply your current settings retroactively.',
					'external-link-manager'
				) }
			/>
			<ScannerJob />
			<BulkApplyJob />
			<UpgradeCard />
		</div>
	);
}
