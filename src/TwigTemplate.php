<?php

namespace Wpify\Template;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class TwigTemplate {
	/** @var Environment */
	private Environment $twig;

	/**
	 *
	 * @param string[] $folders
	 * @param string   $compilation_path
	 * @param bool     $debug
	 */
	public function __construct( array $folders, string $compilation_path, bool $debug = false ) {
		$loader     = new FilesystemLoader( array_map( 'untrailingslashit', $folders ) );
		$this->twig = new Environment( $loader, [
			'cache' => $compilation_path,
			'debug' => $debug
		] );
		if ( $debug ) {
			$this->twig->addExtension( new DebugExtension() );
		}
	}

	/**
	 * Renders the template and prints the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param array  $args Additional arguments passed to the template.
	 *
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function print( string $slug, array $args = array() ): void {
		echo $this->render( $slug, $args );
	}

	/**
	 * Renders the template and returns the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param array  $args Additional arguments passed to the template.
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function render( string $slug, array $args = array() ): string {
		ob_start();

		echo $this->twig->render( $slug, $args );

		return ob_get_clean();
	}
}
