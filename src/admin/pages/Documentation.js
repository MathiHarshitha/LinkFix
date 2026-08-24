import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import PageHeader from '../components/PageHeader';
import UpgradeCard from '../components/UpgradeCard';

const HOOKS = [
	{
		name: 'elm_should_process_link',
		type: 'filter',
		args: '( bool $should, string $url, array $context )',
	},
	{
		name: 'elm_link_attributes',
		type: 'filter',
		args: '( array $attributes, string $url, array $context )',
	},
	{
		name: 'elm_excluded_domains',
		type: 'filter',
		args: '( array $entries, string $url )',
	},
	{
		name: 'elm_supported_post_types',
		type: 'filter',
		args: '( array $post_types )',
	},
	{
		name: 'elm_processed_link',
		type: 'action',
		args: '( array $change, array $context )',
	},
	{
		name: 'elm_scanner_batch_completed',
		type: 'action',
		args: '( array $state )',
	},
	{
		name: 'elm_default_settings',
		type: 'filter',
		args: '( array $defaults )',
	},
	{
		name: 'elm_sanitize_settings',
		type: 'filter',
		args: '( array $clean, array $input )',
	},
	{
		name: 'elm_rule_engine_rules',
		type: 'filter',
		args: '( array $rules, string $url )',
	},
	{
		name: 'elm_rule_engine_matched_actions',
		type: 'filter',
		args: '( array $actions, string $url )',
	},
	{
		name: 'elm_statistics_summary',
		type: 'filter',
		args: '( array $summary )',
	},
	{
		name: 'elm_loaded',
		type: 'action',
		args: '( ELM\\Core\\Plugin $plugin )',
	},
	{
		name: 'elm_extension_registered',
		type: 'action',
		args: '( string $slug, object $instance )',
	},
	{
		name: 'elm_admin_menu_registered',
		type: 'action',
		args: '( string $slug, string $capability )',
	},
	{ name: 'elm_uninstall_cleanup', type: 'action', args: '()' },
];

export default function Documentation() {
	return (
		<div className="elm-page">
			<PageHeader
				title={ __( 'Documentation', 'external-link-manager' ) }
				description={ __(
					'How the plugin works, and the developer API for extending it.',
					'external-link-manager'
				) }
			/>

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __(
							'How link processing works',
							'external-link-manager'
						) }
					</h2>
				</CardHeader>
				<CardBody>
					<p>
						{ __(
							'External links are detected using proper URL parsing (not string matching) and rewritten with WP_HTML_Tag_Processor — never regex. Existing rel values are preserved and merged, never overwritten.',
							'external-link-manager'
						) }
					</p>
					<p>
						{ __(
							'The live rewrite happens server-side on the_content and never loads any JavaScript on your frontend. The Scanner separately indexes links for statistics without modifying content; "Apply to existing content" is the only action that writes changes into stored post content, and only when you explicitly run it.',
							'external-link-manager'
						) }
					</p>
				</CardBody>
			</Card>

			<Card className="elm-card">
				<CardHeader>
					<h2>
						{ __( 'Developer hooks', 'external-link-manager' ) }
					</h2>
				</CardHeader>
				<CardBody>
					<table className="elm-table">
						<thead>
							<tr>
								<th>
									{ __( 'Hook', 'external-link-manager' ) }
								</th>
								<th>
									{ __( 'Type', 'external-link-manager' ) }
								</th>
								<th>
									{ __(
										'Signature',
										'external-link-manager'
									) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ HOOKS.map( ( hook ) => (
								<tr key={ hook.name }>
									<td>
										<code>{ hook.name }</code>
									</td>
									<td>{ hook.type }</td>
									<td>
										<code>{ hook.args }</code>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</CardBody>
			</Card>

			<UpgradeCard />
		</div>
	);
}
