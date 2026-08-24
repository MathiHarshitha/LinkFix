import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	SelectControl,
	TextControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { trash, plus } from '@wordpress/icons';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

const TYPE_LABELS = {
	domain: __( 'Domain', 'external-link-manager' ),
	subdomain: __( 'Domain + subdomains', 'external-link-manager' ),
	url: __( 'Exact URL', 'external-link-manager' ),
	pattern: __( 'URL pattern (wildcards)', 'external-link-manager' ),
};

const PLACEHOLDERS = {
	domain: 'example.com',
	subdomain: 'example.com',
	url: 'https://example.com/page',
	pattern: 'example.com/partners/*',
};

const emptyEntry = () => ( { type: 'domain', value: '' } );

export default function Exclusions() {
	const [ entries, setEntries ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		api.getExclusions().then( ( data ) => setEntries( data || [] ) );
	}, [] );

	const updateEntry = ( index, patch ) =>
		setEntries(
			entries.map( ( e, i ) => ( i === index ? { ...e, ...patch } : e ) )
		);

	const removeEntry = ( index ) =>
		setEntries( entries.filter( ( _, i ) => i !== index ) );

	const addEntry = () => setEntries( [ ...entries, emptyEntry() ] );

	const save = () => {
		setSaving( true );
		api.saveExclusions( entries )
			.then( ( data ) => {
				setEntries( data || [] );
				setNotice( {
					status: 'success',
					message: __( 'Exclusions saved.', 'external-link-manager' ),
				} );
			} )
			.catch( ( err ) =>
				setNotice( {
					status: 'error',
					message: err.message || String( err ),
				} )
			)
			.finally( () => setSaving( false ) );
	};

	if ( ! entries ) {
		return <Spinner />;
	}

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Exclusions', 'external-link-manager' ) }
				description={ __(
					'Links matching any entry below are left completely untouched.',
					'external-link-manager'
				) }
				actions={
					<Button
						variant="primary"
						isBusy={ saving }
						onClick={ save }
					>
						{ __( 'Save exclusions', 'external-link-manager' ) }
					</Button>
				}
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
							'Excluded domains & URLs',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					{ entries.length === 0 && (
						<p>
							{ __(
								'Nothing excluded yet.',
								'external-link-manager'
							) }
						</p>
					) }

					{ entries.map( ( entry, index ) => (
						<div className="elm-rule-row" key={ index }>
							<SelectControl
								label={ __( 'Type', 'external-link-manager' ) }
								value={ entry.type }
								options={ Object.keys( TYPE_LABELS ).map(
									( k ) => ( {
										label: TYPE_LABELS[ k ],
										value: k,
									} )
								) }
								onChange={ ( value ) =>
									updateEntry( index, { type: value } )
								}
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __( 'Value', 'external-link-manager' ) }
								placeholder={ PLACEHOLDERS[ entry.type ] }
								value={ entry.value }
								onChange={ ( value ) =>
									updateEntry( index, { value } )
								}
								__nextHasNoMarginBottom
							/>
							<Button
								icon={ trash }
								label={ __(
									'Remove',
									'external-link-manager'
								) }
								onClick={ () => removeEntry( index ) }
								isDestructive
							/>
						</div>
					) ) }

					<Button
						variant="secondary"
						icon={ plus }
						onClick={ addEntry }
					>
						{ __( 'Add exclusion', 'external-link-manager' ) }
					</Button>
				</CardBody>
			</Card>

			<UpgradeCard />
		</div>
	);
}
