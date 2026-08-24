import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	ToggleControl,
	Notice,
	TextareaControl,
} from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

export default function Tools() {
	const [ info, setInfo ] = useState( null );
	const [ removeOnUninstall, setRemoveOnUninstall ] = useState( false );
	const [ exportJson, setExportJson ] = useState( '' );
	const [ importJson, setImportJson ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ confirmClear, setConfirmClear ] = useState( false );

	useEffect( () => {
		api.getSystemInfo().then( setInfo );
		api.getUninstallPreference().then( ( r ) =>
			setRemoveOnUninstall( r.remove_data_on_uninstall )
		);
	}, [] );

	const toggleUninstall = ( value ) => {
		setRemoveOnUninstall( value );
		api.setUninstallPreference( value );
	};

	const doExport = () => {
		api.exportData().then( ( data ) =>
			setExportJson( JSON.stringify( data, null, 2 ) )
		);
	};

	const doImport = () => {
		try {
			const payload = JSON.parse( importJson );
			api.importData( payload ).then( () =>
				setNotice( {
					status: 'success',
					message: __( 'Import complete.', 'external-link-manager' ),
				} )
			);
		} catch ( e ) {
			setNotice( {
				status: 'error',
				message: __(
					'That file is not valid JSON.',
					'external-link-manager'
				),
			} );
		}
	};

	const onFileChosen = ( event ) => {
		const file = event.target.files && event.target.files[ 0 ];
		if ( ! file ) {
			return;
		}
		const reader = new FileReader();
		reader.onload = () => setImportJson( String( reader.result || '' ) );
		reader.readAsText( file );
	};

	const doClearIndex = () => {
		api.clearIndex().then( () => {
			setConfirmClear( false );
			setNotice( {
				status: 'success',
				message: __( 'Scan index cleared.', 'external-link-manager' ),
			} );
		} );
	};

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Tools', 'external-link-manager' ) }
				description={ __(
					'Export/import configuration, clear scan data, and view system info.',
					'external-link-manager'
				) }
			/>

			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __(
							'Export configuration',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					<p>
						{ __(
							'Exports settings, rules and exclusions as JSON.',
							'external-link-manager'
						) }
					</p>
					<Button variant="secondary" onClick={ doExport }>
						{ __( 'Generate export', 'external-link-manager' ) }
					</Button>
					{ exportJson && (
						<TextareaControl
							value={ exportJson }
							readOnly
							rows={ 10 }
							__nextHasNoMarginBottom
						/>
					) }
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __(
							'Import configuration',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					<input
						type="file"
						accept="application/json"
						onChange={ onFileChosen }
					/>
					<TextareaControl
						label={ __( 'Or paste JSON', 'external-link-manager' ) }
						value={ importJson }
						onChange={ setImportJson }
						rows={ 10 }
						__nextHasNoMarginBottom
					/>
					<Button
						variant="primary"
						onClick={ doImport }
						disabled={ ! importJson }
					>
						{ __( 'Import', 'external-link-manager' ) }
					</Button>
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>{ __( 'Scan data', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					<p>
						{ __(
							'Clears the indexed link data used for statistics. Your content is never touched.',
							'external-link-manager'
						) }
					</p>
					{ ! confirmClear ? (
						<Button
							variant="secondary"
							isDestructive
							onClick={ () => setConfirmClear( true ) }
						>
							{ __(
								'Clear scan index…',
								'external-link-manager'
							) }
						</Button>
					) : (
						<div className="elm-confirm">
							<Button
								variant="primary"
								isDestructive
								onClick={ doClearIndex }
							>
								{ __(
									'Yes, clear it',
									'external-link-manager'
								) }
							</Button>
							<Button
								variant="tertiary"
								onClick={ () => setConfirmClear( false ) }
							>
								{ __( 'Cancel', 'external-link-manager' ) }
							</Button>
						</div>
					) }
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>{ __( 'Uninstall', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					<ToggleControl
						label={ __(
							'Remove plugin data on uninstall',
							'external-link-manager'
						) }
						help={ __(
							'Off by default. When enabled, deleting the plugin removes its settings and scan data — never your post content.',
							'external-link-manager'
						) }
						checked={ removeOnUninstall }
						onChange={ toggleUninstall }
						__nextHasNoMarginBottom
					/>
				</CardBody>
			</Card>

			{ info && (
				<Card className="elm-card">
					<CardHeader>
						<h2>
							{ __(
								'System information',
								'external-link-manager'
							) }
						</h2>
					</CardHeader>
					<CardBody>
						<table className="elm-table">
							<tbody>
								<tr>
									<td>
										{ __(
											'Plugin version',
											'external-link-manager'
										) }
									</td>
									<td>{ info.elm_version }</td>
								</tr>
								<tr>
									<td>
										{ __(
											'Database version',
											'external-link-manager'
										) }
									</td>
									<td>{ info.db_version }</td>
								</tr>
								<tr>
									<td>
										{ __(
											'WordPress version',
											'external-link-manager'
										) }
									</td>
									<td>{ info.wp_version }</td>
								</tr>
								<tr>
									<td>
										{ __(
											'PHP version',
											'external-link-manager'
										) }
									</td>
									<td>{ info.php_version }</td>
								</tr>
								<tr>
									<td>
										{ __(
											'HTML API available',
											'external-link-manager'
										) }
									</td>
									<td>
										{ info.html_api_available
											? __(
													'Yes',
													'external-link-manager'
											  )
											: __(
													'No',
													'external-link-manager'
											  ) }
									</td>
								</tr>
								<tr>
									<td>
										{ __(
											'Active extensions',
											'external-link-manager'
										) }
									</td>
									<td>
										{ info.extensions.length
											? info.extensions.join( ', ' )
											: __(
													'None',
													'external-link-manager'
											  ) }
									</td>
								</tr>
							</tbody>
						</table>
					</CardBody>
				</Card>
			) }

			<UpgradeCard />
		</div>
	);
}
