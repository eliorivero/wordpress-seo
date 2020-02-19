<?php
namespace Yoast\WP\SEO\Tests\Mocks;

use Yoast\WP\SEO\Context\Meta_Tags_Context as Meta_Tags_Context_Original;

class Author extends \Yoast\WP\SEO\Presentations\Generators\Schema\Author {

	/**
	 * @inheritDoc
	 */
	public function build_person_data( $user_id, Meta_Tags_Context_Original $context ) {
		return parent::build_person_data( $user_id, $context );
	}
}
