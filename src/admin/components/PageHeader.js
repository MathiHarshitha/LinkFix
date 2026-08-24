export default function PageHeader( { title, description, actions } ) {
	return (
		<header className="elm-page-header">
			<div>
				<h1>{ title }</h1>
				{ description && (
					<p className="elm-page-header__description">
						{ description }
					</p>
				) }
			</div>
			{ actions && (
				<div className="elm-page-header__actions">{ actions }</div>
			) }
		</header>
	);
}
