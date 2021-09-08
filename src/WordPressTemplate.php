<?php

namespace Wpify\Template;

class WordPressTemplate {
	/** @var string[] */
	private $folders;

	/** @var string */
	private $theme_folder;

	/**
	 * @param string[] $folders
	 * @param ?string $theme_folder
	 */
	public function __construct( array $folders, string $theme_folder = null ) {
		$this->folders      = array_map( 'untrailingslashit', $folders );
		$this->theme_folder = untrailingslashit( $theme_folder );
	}

	/**
	 * Renders the template and prints the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string|null $name The name of the specialised template.
	 * @param array $args Additional arguments passed to the template.
	 */
	public function print( string $slug, string $name = null, array $args = array() ): void {
		echo $this->render( $slug, $name, $args );
	}

	/**
	 * Renders the template and returns the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string|null $name The name of the specialised template.
	 * @param array $args Additional arguments passed to the template.
	 *
	 * @return string
	 */
	public function render( string $slug, string $name = null, array $args = array() ): string {
		ob_start();

		if ( ! empty( $this->theme_folder ) ) {
			$rendered = get_template_part( $this->theme_folder . '/' . $slug, $name, $args ) !== false;
		} else {
			$rendered = get_template_part( $slug, $name, $args ) !== false;
		}

		if ( ! $rendered ) {
			$templates = array();

			foreach ( $this->folders as $folder ) {
				if ( ! empty( $name ) ) {
					$templates[] = $folder . '/' . $slug . '-' . $name . '.php';
				}
			}

			foreach ( $this->folders as $folder ) {
				$templates[] = $folder . '/' . $slug . '.php';
			}

			foreach ( $templates as $template ) {
				if ( file_exists( $template ) ) {
					load_template( $template, false, $args );
					break;
				}
			}
		}

		return ob_get_clean();
	}
}
