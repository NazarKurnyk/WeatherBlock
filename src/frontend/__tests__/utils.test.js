/**
 * Unit tests for frontend utility functions.
 */
import { esc, buildWidget } from '../utils';

// ─── esc() ────────────────────────────────────────────────────────────────────

describe( 'esc()', () => {
	it( 'escapes < and >', () => {
		expect( esc( '<b>bold</b>' ) ).toBe( '&lt;b&gt;bold&lt;/b&gt;' );
	} );

	it( 'escapes ampersand', () => {
		expect( esc( 'A & B' ) ).toBe( 'A &amp; B' );
	} );

	it( 'escapes double quotes', () => {
		expect( esc( '"hello"' ) ).toBe( '&quot;hello&quot;' );
	} );

	it( 'escapes single quotes', () => {
		expect( esc( "it's" ) ).toBe( 'it&#039;s' );
	} );

	it( 'passes through safe text unchanged', () => {
		expect( esc( 'Hello World' ) ).toBe( 'Hello World' );
	} );

	it( 'prevents script injection', () => {
		const malicious = '<script>alert("xss")</script>';
		const escaped = esc( malicious );
		expect( escaped ).not.toContain( '<script>' );
		expect( escaped ).toBe(
			'&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
		);
	} );

	it( 'coerces numbers to string', () => {
		expect( esc( 42 ) ).toBe( '42' );
		expect( esc( 3.14 ) ).toBe( '3.14' );
	} );

	it( 'coerces null to string "null"', () => {
		expect( esc( null ) ).toBe( 'null' );
	} );

	it( 'handles empty string', () => {
		expect( esc( '' ) ).toBe( '' );
	} );
} );

// ─── buildWidget() ────────────────────────────────────────────────────────────

const i18n = {
	temperature: 'Temperature',
	feelsLike: 'Feels like',
	humidity: 'Humidity',
	pressure: 'Pressure',
	windSpeed: 'Wind',
	sunrise: 'Sunrise',
	sunset: 'Sunset',
};

const ALL_VISIBLE = {
	showLocation: true,
	showTemperature: true,
	showFeelsLike: true,
	showCondition: true,
	showHumidity: true,
	showPressure: true,
	showWindSpeed: true,
	showSunrise: true,
	showSunset: true,
};

const NONE_VISIBLE = Object.fromEntries(
	Object.keys( ALL_VISIBLE ).map( ( k ) => [ k, false ] )
);

const SAMPLE_DATA = {
	location: 'Kyiv',
	temperature: 20.5,
	feelsLike: 18.2,
	condition: 'clear sky',
	icon: '01d',
	humidity: 65,
	pressure: 1013,
	windSpeed: 5.5,
	sunrise: '06:12',
	sunset: '20:45',
};

describe( 'buildWidget()', () => {
	// ── Location ─────────────────────────────────────────────────────────────

	it( 'renders location name when showLocation is true', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( 'weather-block__weather-location' );
		expect( html ).toContain( 'Kyiv' );
	} );

	it( 'omits location element when showLocation is false', () => {
		const html = buildWidget(
			SAMPLE_DATA,
			{ ...ALL_VISIBLE, showLocation: false },
			i18n
		);
		expect( html ).not.toContain( 'weather-block__weather-location' );
	} );

	// ── Condition ────────────────────────────────────────────────────────────

	it( 'renders condition when showCondition is true', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( 'clear sky' );
	} );

	it( 'omits condition element when showCondition is false', () => {
		const html = buildWidget(
			SAMPLE_DATA,
			{ ...ALL_VISIBLE, showCondition: false },
			i18n
		);
		expect( html ).not.toContain( 'weather-block__weather-condition' );
	} );

	// ── Temperature ──────────────────────────────────────────────────────────

	it( 'renders temperature row when showTemperature is true', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( '20.5' );
		expect( html ).toContain( 'Temperature' );
	} );

	it( 'omits temperature row when showTemperature is false', () => {
		const html = buildWidget(
			SAMPLE_DATA,
			{ ...ALL_VISIBLE, showTemperature: false },
			i18n
		);
		expect( html ).not.toContain( '20.5' );
	} );

	// ── Feels like ───────────────────────────────────────────────────────────

	it( 'renders feels-like row when showFeelsLike is true', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( '18.2' );
	} );

	it( 'omits feels-like when showFeelsLike is false', () => {
		const html = buildWidget(
			SAMPLE_DATA,
			{ ...ALL_VISIBLE, showFeelsLike: false },
			i18n
		);
		expect( html ).not.toContain( '18.2' );
	} );

	// ── Sunrise / sunset ──────────────────────────────────────────────────────

	it( 'renders sunrise and sunset times', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( '06:12' );
		expect( html ).toContain( '20:45' );
	} );

	// ── Icon ──────────────────────────────────────────────────────────────────

	it( 'renders weather icon for a valid OWM icon code', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( 'openweathermap.org/img/wn/01d@2x.png' );
	} );

	it( 'omits icon element when icon field is empty', () => {
		const html = buildWidget(
			{ ...SAMPLE_DATA, icon: '' },
			ALL_VISIBLE,
			i18n
		);
		expect( html ).not.toContain( 'weather-block__weather-icon' );
	} );

	// ── Table ────────────────────────────────────────────────────────────────

	it( 'renders a <table> when at least one data field is visible', () => {
		const html = buildWidget( SAMPLE_DATA, ALL_VISIBLE, i18n );
		expect( html ).toContain( '<table' );
	} );

	it( 'omits <table> when all data fields are hidden', () => {
		const html = buildWidget( SAMPLE_DATA, NONE_VISIBLE, i18n );
		expect( html ).not.toContain( '<table' );
	} );

	// ── Null safety ───────────────────────────────────────────────────────────

	it( 'does not render the word "null" for null temperature', () => {
		const html = buildWidget(
			{ ...SAMPLE_DATA, temperature: null },
			ALL_VISIBLE,
			i18n
		);
		expect( html ).not.toContain( '>null' );
	} );

	it( 'does not throw when all optional fields are null', () => {
		const sparse = {
			location: '',
			temperature: null,
			feelsLike: null,
			condition: '',
			icon: '',
			humidity: null,
			pressure: null,
			windSpeed: null,
			sunrise: null,
			sunset: null,
		};
		expect( () => buildWidget( sparse, ALL_VISIBLE, i18n ) ).not.toThrow();
	} );

	// ── XSS prevention ───────────────────────────────────────────────────────

	it( 'escapes HTML special characters in location', () => {
		const data = { ...SAMPLE_DATA, location: '<script>alert(1)</script>' };
		const html = buildWidget( data, ALL_VISIBLE, i18n );

		expect( html ).not.toContain( '<script>' );
		expect( html ).toContain( '&lt;script&gt;' );
	} );

	it( 'escapes angle brackets in condition to prevent tag injection', () => {
		const data = {
			...SAMPLE_DATA,
			condition: '"><img src=x onerror=alert(1)>',
		};
		const html = buildWidget( data, ALL_VISIBLE, i18n );

		// The < is escaped, so the malicious <img is rendered as text, not HTML.
		expect( html ).toContain( '&lt;img' );
		expect( html ).not.toContain( '"><img' );
	} );

	it( 'rejects an icon value containing non-alphanumeric characters', () => {
		// If icon contains chars like `"`, a whitelist rejects it → no icon rendered.
		const data = { ...SAMPLE_DATA, icon: '01d" onerror="alert(1)' };
		const html = buildWidget( data, ALL_VISIBLE, i18n );

		// The malicious icon should be rejected; the img element must not appear.
		expect( html ).not.toContain( 'onerror=' );
		expect( html ).not.toContain( 'weather-block__weather-icon' );
	} );

	it( 'accepts valid OWM icon codes that pass the whitelist', () => {
		const validIcons = [ '01d', '01n', '10d', '13n', '50d' ];
		validIcons.forEach( ( icon ) => {
			const html = buildWidget(
				{ ...SAMPLE_DATA, icon },
				ALL_VISIBLE,
				i18n
			);
			expect( html ).toContain( `wn/${ icon }@2x.png` );
		} );
	} );
} );
