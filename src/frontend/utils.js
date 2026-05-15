const ESCAPE_MAP = {
	'&': '&amp;',
	'<': '&lt;',
	'>': '&gt;',
	'"': '&quot;',
	"'": '&#039;',
};

export function esc( str ) {
	return String( str ).replace( /[&<>"']/g, ( ch ) => ESCAPE_MAP[ ch ] );
}

export function buildWidget( data, visibility, i18n ) {
	const rows = [];

	if ( visibility.showTemperature && data.temperature !== null ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.temperature ) }</th>
			<td>${ esc( data.temperature ) }&thinsp;°C</td>
		</tr>` );
	}

	if ( visibility.showFeelsLike && data.feelsLike !== null ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.feelsLike ) }</th>
			<td>${ esc( data.feelsLike ) }&thinsp;°C</td>
		</tr>` );
	}

	if ( visibility.showHumidity && data.humidity !== null ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.humidity ) }</th>
			<td>${ esc( data.humidity ) }&thinsp;%</td>
		</tr>` );
	}

	if ( visibility.showPressure && data.pressure !== null ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.pressure ) }</th>
			<td>${ esc( data.pressure ) }&thinsp;hPa</td>
		</tr>` );
	}

	if ( visibility.showWindSpeed && data.windSpeed !== null ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.windSpeed ) }</th>
			<td>${ esc( data.windSpeed ) }&thinsp;m/s</td>
		</tr>` );
	}

	if ( visibility.showSunrise && data.sunrise ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.sunrise ) }</th>
			<td>${ esc( data.sunrise ) }</td>
		</tr>` );
	}

	if ( visibility.showSunset && data.sunset ) {
		rows.push( `<tr>
			<th scope="row">${ esc( i18n.sunset ) }</th>
			<td>${ esc( data.sunset ) }</td>
		</tr>` );
	}

	// OWM icon codes are alphanumeric (e.g. "01d", "10n") — reject anything else.
	const safeIcon =
		data.icon && /^[a-z0-9]+$/i.test( data.icon ) ? data.icon : '';

	const iconHtml = safeIcon
		? `<img
				class="weather-block__weather-icon"
				src="https://openweathermap.org/img/wn/${ safeIcon }@2x.png"
				alt=""
				role="presentation"
				width="64"
				height="64"
				loading="lazy"
			/>`
		: '';

	const locationHtml =
		visibility.showLocation && data.location
			? `<h3 class="weather-block__weather-location">${ esc( data.location ) }</h3>`
			: '';

	const conditionHtml =
		visibility.showCondition && data.condition
			? `<p class="weather-block__weather-condition">${ esc( data.condition ) }</p>`
			: '';

	const tableHtml = rows.length
		? `<table class="weather-block__weather-table">
				<tbody>${ rows.join( '\n' ) }</tbody>
			</table>`
		: '';

	return `
		<div class="weather-block__weather-widget">
			<div class="weather-block__weather-header">
				${ locationHtml }
				${ iconHtml }
				${ conditionHtml }
			</div>
			${ tableHtml }
		</div>
	`;
}
