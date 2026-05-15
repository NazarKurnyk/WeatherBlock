import { esc, buildWidget } from './utils';

async function loadWeather( container ) {
	const lat = container.dataset.lat;
	const lon = container.dataset.lon;

	if ( ! lat || ! lon ) {
		return;
	}

	const { ajaxUrl, nonce } = window.weatherBlockData ?? {};

	if ( ! ajaxUrl || ! nonce ) {
		return;
	}

	const visibility = JSON.parse( container.dataset.visibility || '{}' );
	const i18n = window.weatherBlockI18n ?? {
		temperature: 'Temperature',
		feelsLike: 'Feels like',
		humidity: 'Humidity',
		pressure: 'Pressure',
		windSpeed: 'Wind',
		sunrise: 'Sunrise',
		sunset: 'Sunset',
		error: 'Weather data unavailable.',
	};

	container.setAttribute( 'aria-busy', 'true' );

	try {
		const body = new FormData();
		body.append( 'action', 'weather_block_get_weather' );
		body.append( 'nonce', nonce );
		body.append( 'lat', lat );
		body.append( 'lon', lon );

		const response = await fetch( ajaxUrl, { method: 'POST', body } );

		if ( ! response.ok ) {
			throw new Error( `HTTP ${ response.status }` );
		}

		const json = await response.json();

		if ( ! json.success ) {
			throw new Error( json.data?.message ?? 'Unknown error' );
		}

		container.innerHTML = buildWidget( json.data, visibility, i18n );
	} catch {
		container.innerHTML = `<p class="weather-block__weather-error">${ esc(
			i18n.error
		) }</p>`;
	} finally {
		container.setAttribute( 'aria-busy', 'false' );
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	document.querySelectorAll( '.weather-block__weather' ).forEach( loadWeather );
} );
