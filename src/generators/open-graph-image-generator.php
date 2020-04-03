<?php
/**
 * Generator object for the Open Graph image.
 *
 * @package Yoast\WP\SEO\Generators
 */

namespace Yoast\WP\SEO\Generators;

use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Helpers\Image_Helper;
use Yoast\WP\SEO\Helpers\Open_Graph\Image_Helper as Open_Graph_Image_Helper;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Helpers\Url_Helper;
use Yoast\WP\SEO\Models\Indexable;
use Yoast\WP\SEO\Generators\Generator_Interface;
use Yoast\WP\SEO\Values\Open_Graph\Images;

/**
 * Represents the generator class for the Open Graph images.
 */
class Open_Graph_Image_Generator implements Generator_Interface {

	/**
	 * The Open Graph image helper.
	 *
	 * @var Open_Graph_Image_Helper
	 */
	protected $open_graph_image;

	/**
	 * The image helper.
	 *
	 * @var Image_Helper
	 */
	protected $image;

	/**
	 * The URL helper.
	 *
	 * @var Url_Helper
	 */
	protected $url;

	/**
	 * The options helper.
	 *
	 * @var Options_Helper
	 */
	private $options;

	/**
	 * Images constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param Open_Graph_Image_Helper $open_graph_image Image helper for Open Graph.
	 * @param Image_Helper            $image            The image helper.
	 * @param Options_Helper          $options          The options helper.
	 * @param Url_Helper              $url              The url helper.
	 */
	public function __construct(
		Open_Graph_Image_Helper $open_graph_image,
		Image_Helper $image,
		Options_Helper $options,
		Url_Helper $url
	) {
		$this->open_graph_image = $open_graph_image;
		$this->image            = $image;
		$this->options          = $options;
		$this->url              = $url;
	}

	/**
	 * Retrieves the images for an indexable.
	 *
	 * @param Meta_Tags_Context $context The context.
	 *
	 * @return array The images.
	 */
	public function generate( Meta_Tags_Context $context ) {
		$image_container = $this->get_image_container();

		/**
		 * Filter: wpseo_add_opengraph_images - Allow developers to add images to the Open Graph tags.
		 *
		 * @api Yoast\WP\SEO\Values\Open_Graph\Images The current object.
		 */
		do_action( 'wpseo_add_opengraph_images', $image_container );

		$this->add_from_indexable( $context->indexable, $image_container );
		$this->add_for_object_type( $context->indexable, $image_container );

		/**
		 * Filter: wpseo_add_opengraph_additional_images - Allows to add additional images to the Open Graph tags.
		 *
		 * @api Yoast\WP\SEO\Values\Open_Graph\Images The current object.
		 */
		do_action( 'wpseo_add_opengraph_additional_images', $image_container );

		$this->add_from_default( $image_container );

		return $image_container->get_images();
	}

	/**
	 * Adds an image based on the given indexable.
	 *
	 * @param Indexable $indexable       The indexable.
	 * @param Images    $image_container The image container.
	 */
	protected function add_from_indexable( Indexable $indexable, Images $image_container ) {
		if ( $indexable->open_graph_image ) {
			$meta_data = [];
			if ( $indexable->open_graph_image_meta && is_string( $indexable->open_graph_image_meta ) ) {
				$meta_data = json_decode( $indexable->open_graph_image_meta, true );
			}

			$image_container->add_image(
				\array_merge(
					(array) $meta_data,
					[
						'url' => $indexable->open_graph_image,
					]
				)
			);

			return;
		}

		if ( $indexable->open_graph_image_id ) {
			$image_container->add_image_by_id( $indexable->open_graph_image_id );
		}
	}

	/**
	 * Adds the images for the indexable object type.
	 *
	 * @param Indexable $indexable       The indexable.
	 * @param Images    $image_container The image container.
	 */
	protected function add_for_object_type( Indexable $indexable, Images $image_container ) {
		if ( $image_container->has_images() ) {
			return;
		}

		switch ( $indexable->object_type ) {
			case 'post' :
				if ( $indexable->object_sub_type === 'attachment' ) {
					$this->add_for_attachment( $indexable->object_id, $image_container );

					return;
				}

				$this->add_for_post_type( $indexable, $image_container );

				break;
		}
	}

	/**
	 * Adds the image for an attachment.
	 *
	 * @param int    $attachment_id   The attachment id.
	 * @param Images $image_container The image container.
	 */
	protected function add_for_attachment( $attachment_id, Images $image_container ) {
		if ( ! \wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$image_container->add_image_by_id( $attachment_id );
	}

	/**
	 * Adds the images for a post type.
	 *
	 * @param Indexable $indexable       The indexable.
	 * @param Images    $image_container The image container.
	 */
	protected function add_for_post_type( Indexable $indexable, Images $image_container ) {
		$featured_image_id = $this->image->get_featured_image_id( $indexable->object_id );
		if ( $featured_image_id ) {
			$image_container->add_image_by_id( $featured_image_id );

			return;
		}

		$content_image = $this->image->get_post_content_image( $indexable->object_id );
		if ( $content_image ) {
			$image_container->add_image_by_url( $content_image );
		}
	}

	/**
	 * Retrieves the default Open Graph image.
	 *
	 * @param Images $image_container The image container.
	 */
	protected function add_from_default( Images $image_container ) {
		if ( $image_container->has_images() ) {
			return;
		}

		$default_image_id = $this->options->get( 'og_default_image_id', '' );
		if ( $default_image_id ) {
			$image_container->add_image_by_id( $default_image_id );

			return;
		}

		$default_image_url = $this->options->get( 'og_default_image', '' );
		if ( $default_image_url ) {
			$image_container->add_image_by_url( $default_image_url );
		}
	}

	/**
	 * Retrieves an instance of the image container.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return Images The image container.
	 */
	protected function get_image_container() {
		$image_container = new Images( $this->image, $this->url );
		$image_container->set_helpers( $this->open_graph_image );

		return $image_container;
	}
}