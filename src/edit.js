import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Notice } from '@wordpress/components';

import { PostSelector } from './components/PostSelector';
import { WeatherVisibilityControls } from './components/WeatherVisibilityControls';
import { PostCardPreview } from './components/PostCardPreview';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { postIds, lat, lon, ...visibilityAttrs } = attributes;

	const blockProps = useBlockProps( {
		className: 'weather-block__editor-preview',
	} );

	const postId1 = postIds[ 0 ] ?? 0;
	const postId2 = postIds[ 1 ] ?? 0;
	const postId3 = postIds[ 2 ] ?? 0;

	function setPostId( index, id ) {
		const next = [ ...postIds ];
		next[ index ] = id;
		setAttributes( { postIds: next.filter( Boolean ) } );
	}

	const hasCoords = lat.trim() !== '' && lon.trim() !== '';
	const hasAnyPost = postId1 || postId2 || postId3 || hasCoords;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Post Selection', 'weather-block' ) }
					initialOpen
				>
					<PostSelector
						label={ __( 'Featured post', 'weather-block' ) }
						value={ postId1 }
						onChange={ ( id ) => setPostId( 0, id ) }
					/>
					<PostSelector
						label={ __( 'Second post', 'weather-block' ) }
						value={ postId2 }
						onChange={ ( id ) => setPostId( 1, id ) }
					/>
					{ ! hasCoords && (
						<PostSelector
							label={ __( 'Third post', 'weather-block' ) }
							value={ postId3 }
							onChange={ ( id ) => setPostId( 2, id ) }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Weather (third slot)', 'weather-block' ) }
					initialOpen={ false }
				>
					<p
						style={ {
							fontSize: '12px',
							color: '#757575',
							margin: '0 0 12px',
						} }
					>
						{ __(
							'Fill in coordinates to replace the third post slot with a live weather widget.',
							'weather-block'
						) }
					</p>
					<TextControl
						label={ __( 'Latitude', 'weather-block' ) }
						value={ lat }
						onChange={ ( val ) => setAttributes( { lat: val } ) }
						placeholder="50.4501"
						type="text"
						inputMode="decimal"
						help={ __(
							'Decimal degrees, e.g. 50.4501',
							'weather-block'
						) }
					/>
					<TextControl
						label={ __( 'Longitude', 'weather-block' ) }
						value={ lon }
						onChange={ ( val ) => setAttributes( { lon: val } ) }
						placeholder="30.5234"
						type="text"
						inputMode="decimal"
						help={ __(
							'Decimal degrees, e.g. 30.5234',
							'weather-block'
						) }
					/>
					{ hasCoords && (
						<PanelBody
							title={ __( 'Visible fields', 'weather-block' ) }
							initialOpen={ false }
						>
							<WeatherVisibilityControls
								attributes={ visibilityAttrs }
								setAttributes={ setAttributes }
							/>
						</PanelBody>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ ! hasAnyPost && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Select posts or enter weather coordinates in the sidebar.',
							'weather-block'
						) }
					</Notice>
				) }

				{ hasAnyPost && (
					<div className="weather-block__posts">
						<PostCardPreview
							postId={ postId1 }
							variant="featured"
							placeholder={ __(
								'Select featured post →',
								'weather-block'
							) }
						/>
						<div className="weather-block__posts-secondary">
							<PostCardPreview
								postId={ postId2 }
								variant="secondary"
								placeholder={ __(
									'Select second post →',
									'weather-block'
								) }
							/>

							{ hasCoords ? (
								<div className="weather-block__weather weather-block__weather--editor">
									<div className="weather-block__weather-placeholder">
										<svg
											aria-hidden="true"
											width="32"
											height="32"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="1.5"
										>
											<path d="M12 2v1M12 21v1M4.22 4.22l.7.7M19.07 19.07l.71.71M2 12h1M21 12h1M4.92 19.07l.7-.7M19.07 4.93l.71-.71" />
											<path d="M12 6a6 6 0 1 1 0 12A6 6 0 0 1 12 6z" />
										</svg>
										<p className="weather-block__weather-placeholder-title">
											{ __(
												'Weather Widget',
												'weather-block'
											) }
										</p>
										<p className="weather-block__weather-placeholder-coords">
											{ lat }, { lon }
										</p>
										<p className="weather-block__weather-placeholder-note">
											{ __(
												'Live data renders on the frontend.',
												'weather-block'
											) }
										</p>
									</div>
								</div>
							) : (
								<PostCardPreview
									postId={ postId3 }
									variant="secondary"
									placeholder={ __(
										'Select third post →',
										'weather-block'
									) }
								/>
							) }
						</div>
					</div>
				) }
			</div>
		</>
	);
}
