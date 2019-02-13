<?php
/**
 * Reads our dist manifest and loads our assets
 */
namespace MANIFEST_LOADER;

class AssetLoader {
    /**
     * Our assets version
     * @var  string
     */
    protected $version = '';

    /**
     * For where our distribution files are
     * @var string
     */
    protected $distribution_path = '';

    /**
     * Prefix for our script handles. Makes sure they
     * don't interfere with any other handles
     * @var string
     */
    protected $tag_prefix = '';

    /**
     * Storage for our plugin url - will get fulled in __construct
     * @var string
     */
    protected $plugin_url = '';

    /**
     * Storage for our plugin path -- will get filled in __construct
     * @var string
     */
    protected $plugin_path = '';

    /**
     * Storage for our registered asset handles
     * @var array
     */
    protected $asset_map = [];

    /**
     * Storage for the wp script dependencies
     * @var array
     */
    protected $script_deps = ['jquery'];

    /**
     * Storage for our registered asset handles
     * @var array
     */
    protected $registered_assets = [];

    /**
     * Storage for the manifest filename
     * @var string
     */
    protected $manifest_filename = 'manifest.json';

    /**
     * Storage for our registered style handles
     * @var array
     */
    protected $registered_style_assets = [];

    /**
     * Constructs our loader
     * @param string $plugin_url        the url to the the present project path
     * @param string $plugin_path       the path to the present project
     * @param string $tag_prefix        the tag prefix for the project
     * @param string $version           the version number associated with the assets
     * @param string $distribution_path the path from the project root to the distribution files and where the manifest.json is located
     * @param string $manifest_filename the name of the manifest.json file in case it has a different name
     */
    public function __construct($plugin_url, $plugin_path, $tag_prefix = '', $version = '', $distribution_path = 'dist', $manifest_filename = 'manifest.json') {
        $this->plugin_url = $plugin_url;
        $this->plugin_path = $plugin_path;
        $this->distribution_path = $distribution_path;
        $this->manifest_filename = $manifest_filename;
        $this->version = $version;
        $this->tag_prefix = $tag_prefix;
    }

    /**
     * Reads our manifest and finds the handles and the actual new files and saves
     * their association in assetMap
     * @return void
     */
    protected function readManifest() {
        $manifest = join(
            DIRECTORY_SEPARATOR,
            [
                $this->plugin_path,
                $this->distribution_path,
                $this->manifest_filename
            ]
        );

        if (file_exists($manifest)) {
            $this->asset_map = json_decode(file_get_contents($manifest), TRUE);
            // throw an error if json decode failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('DECODING the manifest failed');
            }
            return;
        }

        // thorw an error if we could not find the manifest
        throw new \Exception('Could not find manifest');
    }

    /**
     * Makes the file url
     * @param  string $filename
     * @return the plugin destination url of the script
     */
    protected function makeDistributionFileUrl($filename = '') {
        return join('/', [$this->plugin_url, $this->distribution_path, $filename]);
    }

    /**
     * makes the script handle by applying our suffix
     * @param  string $script the string filename of the script
     * @return string
     */
    protected function prefixHandle($script = '') {
        if (!empty($this->tag_prefix)) {
            return join('/', [$this->tag_prefix, $script]);
        }

        return $script;
    }

     /**
     * Registers our assets with our $registeredAssets storage and wordpress
     * @param  boolean $enqueue  whether or not to enqueue our assets once registering them is done
     * @return void
     */
    public function registerAssets($enqueue = true) {
        $this->readManifest();
        foreach ($this->asset_map as $handle => $file) {
            $handle = $this->prefixHandle($handle);
            $url = $this->makeDistributionFileUrl($file);

            // put js in registeredAssets and use wp_register_script
            if (strpos($file, '.js') !== false) {
                \wp_register_script($handle, $url, $this->script_deps, $this->version);
                $this->registered_assets[] = $handle;
            }

            // put css in registeredStyleAssets and use wp_register_script
            if (strpos($file, '.css') !== false) {
                \wp_register_style($handle, $url, [], $this->version, 'all');
                $this->registered_style_assets[] = $handle;
            }
        }

        if ($enqueue === true) {
            $this->enqueueAssets();
        }
    }

    /**
     * Enqueues all of the assets registered
     * @return [type] [description]
     */
    public function enqueueAssets() {
        foreach ($this->registered_assets as $handle) {
            \wp_enqueue_script($handle);
        }

        foreach ($this->registered_style_assets as $handle) {
            \wp_enqueue_style($handle);
        }
    }
}
