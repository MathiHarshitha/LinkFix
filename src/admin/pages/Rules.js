import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	SelectControl,
	TextControl,
	ToggleControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { trash, plus } from '@wordpress/icons';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';
import api from '../services/api';

const ACTION_LABELS = {
	exclude: __( 'Exclude from processing', 'external-link-manager' ),
	force_nofollow: __( 'Force nofollow', 'external-link-manager' ),
	force_sponsored: __( 'Force sponsored', 'external-link-manager' ),
	force_ugc: __( 'Force ugc', 'external-link-manager' ),
	force_new_tab: __( 'Force new tab', 'external-link-manager' ),
};

const emptyRule = () => ( {
	id: `rule-${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 7 ) }`,
	action: 'force_nofollow',
	match_type: 'domain',
	match_value: '',
	enabled: true,
} );

export default function Rules() {
	const [ rules, setRules ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		api.getRules().then( ( data ) => setRules( data.rules || [] ) );
	}, [] );

	const updateRule = ( id, patch ) =>
		setRules(
			rules.map( ( r ) => ( r.id === id ? { ...r, ...patch } : r ) )
		);

	const removeRule = ( id ) =>
		setRules( rules.filter( ( r ) => r.id !== id ) );

	const addRule = () => setRules( [ ...rules, emptyRule() ] );

	const save = () => {
		setSaving( true );
		api.saveRules( rules )
			.then( ( data ) => {
				setRules( data.rules || [] );
				setNotice( {
					status: 'success',
					message: __( 'Rules saved.', 'external-link-manager' ),
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

	if ( ! rules ) {
		return <Spinner />;
	}

	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Rules', 'external-link-manager' ) }
				description={ __(
					'Simple per-domain or per-URL overrides. For conditional logic (post type, category, regex, priorities), see Pro.',
					'external-link-manager'
				) }
				actions={
					<Button
						variant="primary"
						isBusy={ saving }
						onClick={ save }
					>
						{ __( 'Save rules', 'external-link-manager' ) }
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
					<h2>{ __( 'Active rules', 'external-link-manager' ) }</h2>
				</CardHeader>
				<CardBody>
					{ rules.length === 0 && (
						<p>
							{ __(
								'No rules yet. Add one below.',
								'external-link-manager'
							) }
						</p>
					) }

					{ rules.map( ( rule ) => (
						<div className="elm-rule-row" key={ rule.id }>
							<ToggleControl
								label={ __(
									'Enabled',
									'external-link-manager'
								) }
								checked={ !! rule.enabled }
								onChange={ ( value ) =>
									updateRule( rule.id, { enabled: value } )
								}
								__nextHasNoMarginBottom
							/>
							<SelectControl
								label={ __(
									'Action',
									'external-link-manager'
								) }
								value={ rule.action }
								options={ Object.keys( ACTION_LABELS ).map(
									( k ) => ( {
										label: ACTION_LABELS[ k ],
										value: k,
									} )
								) }
								onChange={ ( value ) =>
									updateRule( rule.id, { action: value } )
								}
								__nextHasNoMarginBottom
							/>
							<SelectControl
								label={ __( 'Match', 'external-link-manager' ) }
								value={ rule.match_type }
								options={ [
									{
										label: __(
											'Domain',
											'external-link-manager'
										),
										value: 'domain',
									},
									{
										label: __(
											'Exact URL',
											'external-link-manager'
										),
										value: 'url',
									},
								] }
								onChange={ ( value ) =>
									updateRule( rule.id, { match_type: value } )
								}
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={
									'domain' === rule.match_type
										? __(
												'Domain',
												'external-link-manager'
										  )
										: __( 'URL', 'external-link-manager' )
								}
								placeholder={
									'domain' === rule.match_type
										? 'example.com'
										: 'https://example.com/page'
								}
								value={ rule.match_value }
								onChange={ ( value ) =>
									updateRule( rule.id, {
										match_value: value,
									} )
								}
								__nextHasNoMarginBottom
							/>
							<Button
								icon={ trash }
								label={ __(
									'Remove rule',
									'external-link-manager'
								) }
								onClick={ () => removeRule( rule.id ) }
								isDestructive
							/>
						</div>
					) ) }

					<Button
						variant="secondary"
						icon={ plus }
						onClick={ addRule }
					>
						{ __( 'Add rule', 'external-link-manager' ) }
					</Button>
				</CardBody>
			</Card>

			<UpgradeCard />
		</div>
	);
}
