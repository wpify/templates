<?php

namespace Wpify\Templates;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use WP_Filesystem_Base;
use WP_Post;
use WP_Theme;

class TwigTemplates implements Templates {
	/** @var Environment */
	private Environment $twig;

	/**
	 * Template hierarchy files.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/type_template_hierarchy/
	 * @var string[]
	 */
	private array $template_hierarchy = array(
		'404',
		'archive',
		'attachment',
		'author',
		'category',
		'date',
		'embed',
		'frontpage',
		'home',
		'index',
		'page',
		'paged',
		'privacypolicy',
		'search',
		'single',
		'singular',
		'tag',
		'taxonomy',
	);

	private string $extension = 'twig';

	/**
	 *
	 * @param string[] $folders
	 * @param string $compilation_path
	 * @param bool $debug
	 */
	public function __construct( array $folders = array(), array $args = array() ) {
		if ( isset( $args['integrate'] ) && $args['integrate'] ) {
			$folders[] = get_template_directory();

			foreach ( $this->template_hierarchy as $type ) {
				add_filter( $type . '_template_hierarchy', array( $this, 'add_twig_template' ) );
			}

			add_filter( 'template_include', array( $this, 'template_include' ) );
			add_filter( 'comments_template', array( $this, 'comments_template' ) );
			add_filter( 'theme_page_templates', array( $this, 'register_custom_templates' ), 10, 3 );
		}

		$loader = new FilesystemLoader( array_map( 'untrailingslashit', $folders ) );
		$debug  = $args['debug'] ?? false;

		if ( ! empty( $args['cache'] ) ) {
			$cache = $args['cache'];
		} else {
			/** @var WP_Filesystem_Base $wp_filesystem */
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$upload = wp_get_upload_dir();
			$cache  = $upload['basedir'] . '/cache/wpify-twig-templates';
			$wp_filesystem->mkdir( $cache );
		}

		$this->twig = new Environment( $loader, array(
			'cache'       => $cache,
			'debug'       => $debug,
			'auto_reload' => true,
		) );

		if ( $debug ) {
			$this->twig->addExtension( new DebugExtension() );
		}

		$this->twig->addFunction( new TwigFunction( 'get_header', 'get_header' ) );
		$this->twig->addFunction( new TwigFunction( 'have_posts', 'have_posts' ) );
		$this->twig->addFunction( new TwigFunction( 'the_post', 'the_post' ) );
		$this->twig->addFunction( new TwigFunction( 'the_content', 'the_content' ) );
		$this->twig->addFunction( new TwigFunction( 'get_footer', 'get_footer' ) );
	}

	/**
	 * Renders the template and prints the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string|null $name
	 * @param array $args Additional arguments passed to the template.
	 *
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function print( string $slug, string $name = null, array $args = array() ): void {
		echo $this->render( $slug, $name, $args );
	}

	/**
	 * Renders the template and returns the result.
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string|null $name
	 * @param array $args Additional arguments passed to the template.
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function render( string $slug, string $name = null, array $args = array() ): string {
		if ( empty( trim( $name ) ) ) {
			$filename = trim( $slug );
		} else {
			$filename = trim( $slug ) . '-' . trim( $name );
		}

		if ( $this->extension !== substr( $filename, 0 - strlen( $this->extension ) ) ) {
			$filename = $filename . '.' . $this->extension;
		}

		$this->add_globals();

		foreach ( $args as $name => $value ) {
			$this->twig->addGlobal( $name, $value );
		}

		return $this->twig->render( $filename, $args );
	}

	/**
	 * Add twig templates to array of templates.
	 *
	 * @param array $templates
	 *
	 * @return array
	 */
	public function add_twig_template( array $templates ): array {
		$new_templates = array();

		foreach ( $templates as $template ) {
			$template_name = preg_replace( '/\.\w+$/', '', $template );

			if ( ! in_array( $template_name . '.twig', $templates ) ) {
				$new_templates[] = $template_name . '.twig';
			}

			$new_templates[] = $template;
		}

		return $new_templates;
	}

	/**
	 * Return custom templates from theme directory.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_theme/get_post_templates/
	 *
	 * @param array $page_templates
	 * @param WP_Theme $theme
	 * @param WP_Post|null $post
	 *
	 * @return array
	 */
	public function register_custom_templates( array $page_templates, WP_Theme $theme, WP_Post $post = null ): array {
		$files     = $theme->get_files( $this->extension, 1 );
		$post_type = get_post_type( $post );

		foreach ( $files as $file ) {
			$headers = get_file_data( $file, [
				'template_name' => 'Template Name',
				'post_type'     => 'Template Post Type',
			] );

			if ( ! $headers ) {
				continue;
			}

			if ( ! $headers['template_name'] ) {
				continue;
			}

			if ( ! $headers['post_type'] ) {
				$headers['post_type'] = 'page';
			}

			$template_post_types = explode( ',', $headers['post_type'] );

			foreach ( $template_post_types as $template_post_type ) {
				if ( trim( $template_post_type ) === $post_type ) {
					$template_name                    = preg_replace( '/^' . preg_quote( get_template_directory() . '/', '/' ) . '/', '', $file );
					$page_templates[ $template_name ] = $headers['template_name'];
				}
			}
		}

		return $page_templates;
	}

	/**
	 * Renders comment template
	 */
	public function comments_template( string $comment_template ): string {
		if ( preg_match( '/\.' . $this->extension . '$/m', $comment_template ) ) {
			$latte_template = $comment_template;
		} else {
			$latte_template = preg_replace( '/.php$/', '.latte', $comment_template );
		}

		if ( file_exists( $latte_template ) ) {
			$comment_template = $this->render( $latte_template );
		}

		return $comment_template;
	}

	/*
	 * Adds global variables.
	 *
	 * @see https://developer.wordpress.org/reference/functions/load_template/
	 */
	public function add_globals() {
		global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

		$variables = array_merge( array(
			'posts'         => $posts,
			'post'          => $post,
			'wp_did_header' => $wp_did_header,
			'wp_query'      => $wp_query,
			'wp_rewrite'    => $wp_rewrite,
			'wpdb'          => $wpdb,
			'wp_version'    => $wp_version,
			'wp'            => $wp,
			'id'            => $id,
			'comment'       => $comment,
			'user_ID'       => $user_ID
		), $wp_query->query_vars );

		foreach ( $variables as $name => $value ) {
			$this->twig->addGlobal( $name, $value );
		}
	}

	public function template_include( string $template ) {
		if ( preg_match( '/\.' . $this->extension . '$/m', $template ) ) {
			$template = str_replace( get_template_directory(), '', $template );

			$this->add_globals();

			$this->print( $template );

			return realpath( join( DIRECTORY_SEPARATOR, array( __DIR__, '..', 'templates', 'empty.php' ) ) );
		}

		return $template;
	}

	public function execute_php() {

	}
}
