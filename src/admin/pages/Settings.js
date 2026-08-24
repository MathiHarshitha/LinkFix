import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
	CheckboxControl,
	RangeControl,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

const TOGGLES = [
	{
		key: 'new_tab',
		label: __(
			'Open external links in a new tab',
			'external-link-manager'
		),
	},
	{
		key: 'noopener',
		label: __( 'Add rel="noopener"', 'external-link-manager' ),
	},
	{
		key: 'noreferrer',
		label: __( 'Add rel="noreferrer"', 'external-link-manager' ),
	},
	{
		key: 'nofollow',
		label: __( 'Add rel="nofollow"', 'external-link-manager' ),
	},
	{
		key: 'sponsored',
		label: __( 'Add rel="sponsored"', 'external-link-manager' ),
	},
	{ key: 'ugc', label: __( 'Add rel="ugc"', 'external-link-manager' ) },
	{
		key: 'statistics_enabled',
		label: __( 'Enable statistics collection', 'external-link-manager' ),
	},
];

export default function Settings() {
	const [ settings, setSettings ] = useState( null );
	const [ postTypes, setPostTypes ] = useState( {} );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		Promise.all( [ api.getSettings(), api.getPostTypes() ] ).then(
			( [ s, pt ] ) => {
				setSettings( s );
				setPostTypes( pt );
			}
		);
	}, [] );

	const update = ( key, value ) =>
		setSettings( { ...settings, [ key ]: value } );

	const toggleContentType = ( slug, checked ) => {
		const current = new Set( settings.content_types || [] );

		if ( checked ) {
			current.add( slug );
		} else {
			current.delete( slug );
		}

		update( 'content_types', Array.from( current ) );
	};

	const save = () => {
		setSaving( true );
		setNotice( null );
		api.updateSettings( settings )
			.then( ( updated ) => {
				setSettings( updated );
				setNotice( {
					status: 'success',
					message: __( 'Settings saved.', 'external-link-manager' ),
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

	if ( ! settings ) {
		return <Spinner />;
	}

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Settings', 'external-link-manager' ) }
				description={ __(
					'Configure how external links are handled across your site.',
					'external-link-manager'
				) }
				actions={
					<Button
						variant="primary"
						isBusy={ saving }
						onClick={ save }
					>
						{ __( 'Save changes', 'external-link-manager' ) }
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
					<h2>{ __( 'Link handling', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					{ TOGGLES.map( ( t ) => (
						<ToggleControl
							key={ t.key }
							label={ t.label }
							checked={ !! settings[ t.key ] }
							onChange={ ( value ) => update( t.key, value ) }
							__nextHasNoMarginBottom
						/>
					) ) }
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>{ __( 'Content scope', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					<p>
						{ __(
							'Choose which content types the plugin processes.',
							'external-link-manager'
						) }
					</p>
					{ Object.keys( postTypes ).map( ( slug ) => (
						<CheckboxControl
							key={ slug }
							label={ postTypes[ slug ] }
							checked={ ( settings.content_types || [] ).includes(
								slug
							) }
							onChange={ ( checked ) =>
								toggleContentType( slug, checked )
							}
							__nextHasNoMarginBottom
						/>
					) ) }
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>{ __( 'Scanner', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					<RangeControl
						label={ __( 'Batch size', 'external-link-manager' ) }
						help={ __(
							'How many items the scanner processes per request. Lower this on slower hosting.',
							'external-link-manager'
						) }
						min={ 5 }
						max={ 200 }
						value={ settings.scanner_batch_size }
						onChange={ ( value ) =>
							update( 'scanner_batch_size', value )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Automatically apply settings to existing content after saving',
							'external-link-manager'
						) }
						help={ __(
							'Off by default. When enabled, saving these settings also runs the bulk apply job.',
							'external-link-manager'
						) }
						checked={ !! settings.auto_apply_existing }
						onChange={ ( value ) =>
							update( 'auto_apply_existing', value )
						}
						__nextHasNoMarginBottom
					/>
				</CardBody>
			</Card>

			<UpgradeCard />
		</div>
	);
}
