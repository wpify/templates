<?php

namespace Wpify\Templates;

/**
 * Load PHP templates the WordPress way.
 *
 * It accepts absolute or relative folders. If relative folder, it tries load templates from theme folder. If absolute
 * folder, it loads from that folder whenever it sits. The order of folders sets its priority.
 */
class WordPressTemplates implements Templates {
	/** @var string[] */
	private array $folders;

	public function __construct( array $folders = array(), array $args = array() ) {
		$this->folders = array_map( 'untrailingslashit', $folders );
	}

	public function print( string $slug, string $name = null, array $args = array() ): void {
		echo $this->render( $slug, $name, $args );
	}

	/**
	 * @throws TemplateNotFoundException
	 */
	public function render( string $slug, string $name = null, array $args = array() ): string {
		ob_start();

		$rendered = false;
		$filename = empty( trim( $name ) ) ? trim( $slug ) : trim( $slug ) . '-' . trim( $name );

		foreach ( $this->folders as $folder ) {
			$folder = trim( $folder );

			if ( 0 === strpos( $folder, '/' ) ) {
				// it's an absolute path
				$template = file_exists( $folder . '/' . $filename . '.php' );

				if ( file_exists( $template ) ) {
					$rendered = true;

					load_template( $template, false, $args );
				}
			} else {
				// it's a relative path, so we try to load a template from theme
				$rendered = get_template_part( $folder . '/' . $slug, $name, $args ) !== false;
			}

			if ( true === $rendered ) {
				break;
			}
		}

		// If the template is not rendered and is relative, we try to load it from theme directly
		if ( false === $rendered && 0 !== strpos( $folder, '/' ) ) {
			$rendered = get_template_part( $slug, $name, $args ) !== false;
		}

		if ( $rendered === false ) {
			throw new TemplateNotFoundException( 'The template ' . $filename . ' was not found.' );
		}

		return ob_get_clean();
	}
}
