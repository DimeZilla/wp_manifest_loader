# WordPress Manifest Loader

This library provides a simple class that will read a `manifest.json` and automatically register and enqueue the scripts found within.

A manifest.json is typically a file that is produced by a bundler like webpack as part of a production build. It could be something that looks like this:

```
{
  "main.css": "main.63d564d3327e37de652f.css",
  "main.js": "main-63d564d3327e37de652f.js"
}
```

## How to use?
It's pretty simple. For the purpose of the following example, I'm going to assume that you are using this in a plugin.

1) The first thing you should do is create the class:
```
$loader = new AssetLoader(\plugin_dir_url(__FILE__), dirname(__FILE__), 'radcampagin', '0.0');
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

Register assets by default automatically enqueues the assets it finds. To prevent this from happening you can call register assets like so:
```
add_action('wp_enqueue_scripts', function () use ($loader) {
    $loader->registerAssets(fales);
    // do some stuff ....
    $loader->enqueueAssets();
});
```
