import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const FIELDS = [
	{ key: 'showLocation', label: __( 'Location name', 'weather-block' ) },
	{ key: 'showTemperature', label: __( 'Temperature', 'weather-block' ) },
	{ key: 'showFeelsLike', label: __( 'Feels-like temperature', 'weather-block' ) },
	{ key: 'showCondition', label: __( 'Weather condition', 'weather-block' ) },
	{ key: 'showHumidity', label: __( 'Humidity', 'weather-block' ) },
	{ key: 'showPressure', label: __( 'Pressure', 'weather-block' ) },
	{ key: 'showWindSpeed', label: __( 'Wind speed', 'weather-block' ) },
	{ key: 'showSunrise', label: __( 'Sunrise', 'weather-block' ) },
	{ key: 'showSunset', label: __( 'Sunset', 'weather-block' ) },
];

export function WeatherVisibilityControls( { attributes, setAttributes } ) {
	return (
		<>
			{ FIELDS.map( ( { key, label } ) => (
				<ToggleControl
					key={ key }
					label={ label }
					checked={ !! attributes[ key ] }
					onChange={ ( val ) => setAttributes( { [ key ]: val } ) }
				/>
			) ) }
		</>
	);
}
