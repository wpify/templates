# WPify Taxonomy

Abstraction over WordPress Templates.

## Installation

`composer require wpify/template`

## Usage

```php
use Wpify\Template\WordPressTemplate;

// Initialize the templates
$template = new WordPressTemplate(
	array( plugin_dir_path( __FILE__ ) . 'templates' ), // path to template files in plugin
	'my-plugin-theme-folder' // folder in current theme
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
