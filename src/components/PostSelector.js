import { useState } from '@wordpress/element';
import { ComboboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

export function PostSelector( { label, value, onChange } ) {
	const [ search, setSearch ] = useState( '' );

	const posts = useSelect(
		( select ) =>
			select( coreDataStore ).getEntityRecords( 'postType', 'post', {
				search,
				per_page: 10,
				status: 'publish',
				_fields: 'id,title',
			} ) ?? [],
		[ search ]
	);

	const selectedPost = useSelect(
		( select ) => {
			if ( ! value ) {
				return null;
			}
			return select( coreDataStore ).getEntityRecord(
				'postType',
				'post',
				value,
				{ _fields: 'id,title' }
			);
		},
		[ value ]
	);

	const options = posts.map( ( post ) => ( {
		value: String( post.id ),
		label: decodeEntities( post.title?.rendered ?? `Post #${ post.id }` ),
	} ) );

	// Keep the selected post visible even when it's not in the current search results.
	if ( selectedPost && ! options.find( ( o ) => o.value === String( value ) ) ) {
		options.unshift( {
			value: String( selectedPost.id ),
			label: decodeEntities(
				selectedPost.title?.rendered ?? `Post #${ selectedPost.id }`
			),
		} );
	}

	return (
		<ComboboxControl
			label={ label }
			value={ value ? String( value ) : '' }
			options={ options }
			onFilterValueChange={ ( val ) => setSearch( val ) }
			onChange={ ( val ) => onChange( val ? parseInt( val, 10 ) : 0 ) }
			allowReset
		/>
	);
}
