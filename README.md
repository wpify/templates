# WPify Template

Abstraction over WordPress Templates.

## Installation

`composer require wpify/templates`

## Usage

```php
use Wpify\Templates\WordPressTemplates;

// Initialize the templates
$template = new WordPressTemplates(
	array(
		plugin_dir_path( __FILE__ ) . 'templates', // path to template files in plugin
		get_template_directory() . 'my-plugin', // path to template files in current theme 
	), 
);

// Print the html to frontend 
$template->print( 'my-template', 'test', array( 'some-args' => 'test' ) );

// Return the html
$html = $template->render( 'my-template', 'test', array( 'some-args' => 'test' ) );
```

The above examples tries to find the templates in the following locations:

* `/wp-content/themes/current-theme/my-plugin-theme-folder/my-template.test.php`
* `/wp-content/themes/current-theme/my-plugin-theme-folder/my-template.php`
* `/wp-content/plugins/my-plugin/templates/my-template.test.php`
* `/wp-content/plugins/my-plugin/templates/my-template.php`

## Twig templates

You can also use twig templates for rendering. WordPress global variables and some functions are already registered.

```php
use Wpify\Templates\TwigTemplates;

// Initialize the templates
$template = new TwigTemplates(
	array(
		plugin_dir_path( __FILE__ ) . 'templates', // path to template files in plugin
		get_template_directory() . 'my-plugin', // path to template files in current theme 
	),
	array(
		'integrate' => true, // Allows twig templates for the current theme
		'functions' => array( // Register custom functions.
			'test_function' => function() {
				echo 'TEST';
			}
		),
		'globals' => array( // Register global variables.
			'global_variable' => 'some value',
		)
	)
);

// Print the html to frontend 
$template->print( 'my-template', 'test', array( 'some-args' => 'test' ) );

// Return the html
$html = $template->render( 'my-template', 'test', array( 'some-args' => 'test' ) );
```
