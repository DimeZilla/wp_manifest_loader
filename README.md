# WordPress Manifest Loader

This library provides a simple class that will read a `manifest.json` and automatically register and enqueue the scripts found within.

A manifest.json is typically a file that is produced by a bundler like webpack as part of a production build. It could be something that looks like this:

```
{
  "main.css": "main.63d564d3327e37de652f.css",
  "main.js": "main-63d564d3327e37de652f.js"
}
```

## Installation
Install via composer:
```
composer require dimezilla/wp_manifest_loader
```

## How to use?
It's pretty simple. For the purpose of the following example, I'm going to assume that you are using this in a plugin.

1) The first thing you should do is create the class:
```
$loader = new MANIFEST_LOADER\AssetLoader(\plugin_dir_url(__FILE__), dirname(__FILE__), 'radcampagin', '0.0');
```

Here's all of the arguments that the assetloader takes in the order in which they are accepted:
* $plugin_url        - string - the url to the the present project path
* $plugin_path       - string - the path to the present project
* $tag_prefix        - string - the tag prefix for the project
* $version           - string - the version number associated with the assets
* $distribution_path - string - the path from the project root to the distribution files and where the manifest.json is located
* $manifest_filename - string - the name of the manifest.json file in case it has a different name

In the above example, we are telling the asset loader that the url is the wordpress generated url for the plugin project's path. Then we are telling the asset loader that the directory path to our project is this current directory of our file. Next we are telling the loader that the plugin tag prefix is 'radcampaign'. This means that the asset loader will ascribe a handle prefixed with 'radcampaign/' for let's say a file called main.js. Thus the handle would be 'radcampaign/main.js'. So if you want o use it as a script dependency later, in another script, this is how you would use it.

2) Next, call registerAssets inside some sort of `wp_enqueue_scripts` hook
```
add_action('wp_enqueue_scripts', function () use ($loader) {
    $loader->registerAssets();
});
```

`registerAssets` by default automatically enqueues the assets it finds. To prevent this from happening you can call register assets like so:
```
add_action('wp_enqueue_scripts', function () use ($loader) {
    $loader->registerAssets(fales);
    // do some stuff ....
    $loader->enqueueAssets();
});
```

## Advanced configuration
This package has support for a `asset-loader.config.json` to be placed in the same director as the `$plugin_path` setting when the class is instantiated. This file must be valid json and is a dictionary keyed by the script key found in the manifest file. Borrowing from our example above, here's what a possible configuration might look like:
```
{
    "main.js": {
        "dependencies": ["jquery", "lodash"],
        "version": "0.1",
        "in_footer": true
    },
    "main.css": {
        "dependencies": ["bootstrap"],
        "version": "1.0",
        "media": "screen"
    }
}
```

This file would register our main.js script with the jquery and lodash dependencies. It would assign a version of 0.1, and load the script in the footer. This would also register main.css with the a bootstrap dependency, give it a version of 1.0 and specify a "screen" media for the css.
