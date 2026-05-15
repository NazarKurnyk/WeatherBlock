/**
 * Unit tests for WeatherVisibilityControls component.
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { WeatherVisibilityControls } from '../WeatherVisibilityControls';

// ─── Mocks ───────────────────────────────────────────────────────────────────

jest.mock( '@wordpress/components', () => ( {
	ToggleControl: ( { label, checked, onChange } ) => (
		<label>
			<input
				type="checkbox"
				aria-label={ label }
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
			/>
			{ label }
		</label>
	),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( str ) => str,
} ) );

// ─── Fixtures ────────────────────────────────────────────────────────────────

const ALL_ON = {
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

// ─── Tests ───────────────────────────────────────────────────────────────────

describe( 'WeatherVisibilityControls', () => {
	it( 'renders exactly 9 toggle controls', () => {
		render(
			<WeatherVisibilityControls
				attributes={ ALL_ON }
				setAttributes={ jest.fn() }
			/>
		);

		expect( screen.getAllByRole( 'checkbox' ) ).toHaveLength( 9 );
	} );

	it( 'renders a toggle for each expected field label', () => {
		render(
			<WeatherVisibilityControls
				attributes={ ALL_ON }
				setAttributes={ jest.fn() }
			/>
		);

		const expectedLabels = [
			'Location name',
			'Temperature',
			'Feels-like temperature',
			'Weather condition',
			'Humidity',
			'Pressure',
			'Wind speed',
			'Sunrise',
			'Sunset',
		];

		expectedLabels.forEach( ( label ) => {
			expect(
				screen.getByRole( 'checkbox', { name: label } )
			).toBeInTheDocument();
		} );
	} );

	it( 'reflects true attributes as checked checkboxes', () => {
		render(
			<WeatherVisibilityControls
				attributes={ ALL_ON }
				setAttributes={ jest.fn() }
			/>
		);

		screen
			.getAllByRole( 'checkbox' )
			.forEach( ( cb ) => expect( cb ).toBeChecked() );
	} );

	it( 'reflects false attributes as unchecked checkboxes', () => {
		const ALL_OFF = Object.fromEntries(
			Object.keys( ALL_ON ).map( ( k ) => [ k, false ] )
		);

		render(
			<WeatherVisibilityControls
				attributes={ ALL_OFF }
				setAttributes={ jest.fn() }
			/>
		);

		screen
			.getAllByRole( 'checkbox' )
			.forEach( ( cb ) => expect( cb ).not.toBeChecked() );
	} );

	it( 'calls setAttributes with correct key=false when toggled off', async () => {
		const setAttributes = jest.fn();
		const user = userEvent.setup();

		render(
			<WeatherVisibilityControls
				attributes={ ALL_ON }
				setAttributes={ setAttributes }
			/>
		);

		await user.click(
			screen.getByRole( 'checkbox', { name: 'Location name' } )
		);

		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( { showLocation: false } );
	} );

	it( 'calls setAttributes with correct key=true when toggled on', async () => {
		const setAttributes = jest.fn();
		const user = userEvent.setup();

		render(
			<WeatherVisibilityControls
				attributes={ { ...ALL_ON, showHumidity: false } }
				setAttributes={ setAttributes }
			/>
		);

		await user.click(
			screen.getByRole( 'checkbox', { name: 'Humidity' } )
		);

		expect( setAttributes ).toHaveBeenCalledWith( { showHumidity: true } );
	} );

	it( 'only calls setAttributes for the clicked toggle', async () => {
		const setAttributes = jest.fn();
		const user = userEvent.setup();

		render(
			<WeatherVisibilityControls
				attributes={ ALL_ON }
				setAttributes={ setAttributes }
			/>
		);

		await user.click(
			screen.getByRole( 'checkbox', { name: 'Pressure' } )
		);

		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		const [ calledWith ] = setAttributes.mock.calls[ 0 ];
		expect( Object.keys( calledWith ) ).toEqual( [ 'showPressure' ] );
	} );
} );
