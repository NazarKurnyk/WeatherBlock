import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

const PlaceholderIcon = () => (
	<svg
		aria-hidden="true"
		width="32"
		height="32"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.5"
	>
		<rect x="3" y="3" width="18" height="18" rx="2" />
		<circle cx="8.5" cy="8.5" r="1.5" />
		<path d="M21 15l-5-5L5 21" />
	</svg>
);

export function PostCardPreview( {
	postId,
	variant = 'featured',
	placeholder,
} ) {
	const variantClass = `weather-block__post-card--${ variant }`;

	const post = useSelect(
		( select ) => {
			if ( ! postId ) {
				return null;
			}
			return select( coreDataStore ).getEntityRecord(
				'postType',
				'post',
				postId,
				{ _embed: true }
			);
		},
		[ postId ]
	);

	const isResolving = useSelect(
		( select ) => {
			if ( ! postId ) {
				return false;
			}
			return select( coreDataStore ).isResolving( 'getEntityRecord', [
				'postType',
				'post',
				postId,
			] );
		},
		[ postId ]
	);

	if ( ! postId ) {
		return (
			<div
				className={ `weather-block__post-card ${ variantClass } weather-block__post-card--empty` }
			>
				<div className="weather-block__post-card-image weather-block__post-card-image--placeholder">
					<PlaceholderIcon />
				</div>
				<div className="weather-block__post-card-body">
					<p className="weather-block__post-card-empty-label">
						{ placeholder }
					</p>
				</div>
			</div>
		);
	}

	if ( isResolving || post === undefined ) {
		return (
			<div
				className={ `weather-block__post-card ${ variantClass } weather-block__post-card--loading` }
			>
				<Spinner />
			</div>
		);
	}

	if ( ! post ) {
		return (
			<div
				className={ `weather-block__post-card ${ variantClass } weather-block__post-card--error` }
			>
				<p>{ __( 'Post not found.', 'weather-block' ) }</p>
			</div>
		);
	}

	const title = decodeEntities( post.title?.rendered ?? '' );
	const imageUrl =
		post._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url ?? null;
	const category = post._embedded?.[ 'wp:term' ]?.[ 0 ]?.[ 0 ]?.name ?? '';
	const author = post._embedded?.author?.[ 0 ]?.name ?? '';
	const TitleTag = variant === 'featured' ? 'h2' : 'h3';

	return (
		<article className={ `weather-block__post-card ${ variantClass }` }>
			<div
				className={ `weather-block__post-card-image${
					! imageUrl
						? ' weather-block__post-card-image--placeholder'
						: ''
				}` }
			>
				{ imageUrl ? (
					<img src={ imageUrl } alt={ title } loading="lazy" />
				) : (
					<PlaceholderIcon />
				) }
			</div>
			<div className="weather-block__post-card-body">
				{ category && (
					<span className="weather-block__post-card-category">
						{ category }
					</span>
				) }
				<TitleTag className="weather-block__post-card-title">
					{ title }
				</TitleTag>
				{ variant === 'featured' && author && (
					<div className="weather-block__post-card-meta">
						<span className="weather-block__post-card-author">
							{ author }
						</span>
					</div>
				) }
			</div>
		</article>
	);
}
