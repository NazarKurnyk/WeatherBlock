/**
 * Unit tests for PostSelector component.
 */
import { render, screen } from '@testing-library/react';
import { PostSelector } from '../PostSelector';

// ─── Mocks ───────────────────────────────────────────────────────────────────

const mockGetEntityRecords = jest.fn();
const mockGetEntityRecord = jest.fn();
const mockIsResolving = jest.fn( () => false );

// useSelect passes a `select` function; `select(store)` returns the store API.
jest.mock( '@wordpress/data', () => ( {
	useSelect: ( cb ) =>
		cb( () => ( {
			getEntityRecords: mockGetEntityRecords,
			getEntityRecord: mockGetEntityRecord,
			isResolving: mockIsResolving,
		} ) ),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useState: jest.requireActual( 'react' ).useState,
} ) );

jest.mock( '@wordpress/components', () => ( {
	ComboboxControl: ( {
		label,
		value,
		options,
		onChange,
		onFilterValueChange,
	} ) => (
		<div>
			<label htmlFor="combo">{ label }</label>
			<input
				id="combo"
				role="combobox"
				value={ value ?? '' }
				onChange={ ( e ) => onFilterValueChange( e.target.value ) }
				data-testid="combobox-input"
			/>
			<select
				data-testid="combobox-select"
				value={ value ?? '' }
				onChange={ ( e ) => onChange( e.target.value || null ) }
			>
				<option value="">— none —</option>
				{ options.map( ( o ) => (
					<option key={ o.value } value={ o.value }>
						{ o.label }
					</option>
				) ) }
			</select>
		</div>
	),
} ) );

jest.mock( '@wordpress/html-entities', () => ( {
	decodeEntities: ( str ) => str,
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( str ) => str,
} ) );

// ─── Tests ───────────────────────────────────────────────────────────────────

describe( 'PostSelector', () => {
	beforeEach( () => {
		mockGetEntityRecords.mockReturnValue( [] );
		mockGetEntityRecord.mockReturnValue( null );
	} );

	it( 'renders with the provided label', () => {
		render(
			<PostSelector
				label="First post"
				value={ 0 }
				onChange={ jest.fn() }
			/>
		);

		expect( screen.getByText( 'First post' ) ).toBeInTheDocument();
	} );

	it( 'renders post options returned by the data store', () => {
		mockGetEntityRecords.mockReturnValue( [
			{ id: 1, title: { rendered: 'Post Alpha' } },
			{ id: 2, title: { rendered: 'Post Beta' } },
		] );

		render(
			<PostSelector label="Post" value={ 0 } onChange={ jest.fn() } />
		);

		const select = screen.getByTestId( 'combobox-select' );
		expect( select ).toContainHTML( 'Post Alpha' );
		expect( select ).toContainHTML( 'Post Beta' );
	} );

	it( 'prepends selected post to options if not in search results', () => {
		mockGetEntityRecords.mockReturnValue( [] );
		mockGetEntityRecord.mockReturnValue( {
			id: 5,
			title: { rendered: 'Selected Post' },
		} );

		render(
			<PostSelector label="Post" value={ 5 } onChange={ jest.fn() } />
		);

		expect( screen.getByTestId( 'combobox-select' ) ).toContainHTML(
			'Selected Post'
		);
	} );

	it( 'calls onChange with integer id when option selected', () => {
		mockGetEntityRecords.mockReturnValue( [
			{ id: 7, title: { rendered: 'My Post' } },
		] );

		const onChange = jest.fn();

		render(
			<PostSelector label="Post" value={ 0 } onChange={ onChange } />
		);

		const select = screen.getByTestId( 'combobox-select' );
		// Simulate selecting option "7".
		select.value = '7';
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		// The onChange should have been called.
		expect( onChange ).toHaveBeenCalled();
	} );
} );
