export default function StatCard( {
	label,
	value,
	sublabel,
	tone = 'default',
} ) {
	return (
		<div className={ `elm-stat-card elm-stat-card--${ tone }` }>
			<div className="elm-stat-card__value">{ value }</div>
			<div className="elm-stat-card__label">{ label }</div>
			{ sublabel && (
				<div className="elm-stat-card__sublabel">{ sublabel }</div>
			) }
		</div>
	);
}
