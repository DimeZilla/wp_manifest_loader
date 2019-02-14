<?php
/**
 * Reads our dist manifest and loads our assets
 */
namespace MANIFEST_LOADER;

class AssetLoader
{
    /**
     * Our assets version
     * @var  string
     */
    protected $version = '1.0';

    /**
     * For where our distribution files are
     * @var string
     */
    protected $distribution_path = 'dist';

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
    protected $plugin_url = '/';

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
    protected $script_deps = [];

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
     * Storage for our config that can be loaded from the root directory
     * @var array
     */
    protected $config = [];

    /**
     * Constructs our loader
     * @param string $plugin_url        the url to the the present project path
     * @param string $plugin_path       the path to the present project
     * @param string $tag_prefix        the tag prefix for the project
     * @param string $version           the version number associated with the assets
     * @param string $distribution_path the path from the project root to the distribution files and where the manifest.json is located
     * @param string $manifest_filename the name of the manifest.json file in case it has a different name
     */
    public function __construct($plugin_url = '', $plugin_path = '', $tag_prefix = '', $version = '', $distribution_path = '', $manifest_filename = '', $load_config = true, $read_manifest = true)
    {
        if (!empty($plugin_url)) {
            $this->setPluginUrl($plugin_url);
        }

        if (!empty($plugin_path)) {
            $this->setPluginPath($plugin_path);
        }

        if (!empty($distribution_path)) {
            $this->setDistributionPath($distribution_path);
        }

        if (!empty($manifest_filename)) {
            $this->setManifestFilename($manifest_filename);
        }

        if (!empty($version)) {
            $this->setVersion($version);
        }

        if (!empty($tag_prefix)) {
            $this->setTagPrefix($tag_prefix);
        }

        if ($load_config) {
            $this->loadConfig();
        }

        if ($read_manifest) {
            $this->readManifest();
        }
    }
    /**
     * [create description]
     * @param string $plugin_url        the url to the the present project path
     * @param string $plugin_path       the path to the present project
     * @param string $tag_prefix        the tag prefix for the project
     * @param string $version           the version number associated with the assets
     * @param string $distribution_path the path from the project root to the distribution files and where the manifest.json is located
     * @param boolean $load_config      look for a config file and load it automatically
     * @param boolean $read_manifest    look for the manifest and read it automatically
     * @return self
     */
    public static function create($plugin_url = '', $plugin_path = '', $tag_prefix = '', $version = '', $distribution_path = '', $manifest_filename = '', $load_config = true, $read_manifest = true)
    {
        return new static(
            $plugin_url,
            $plugin_path,
            $tag_prefix,
            $version,
            $distribution_path,
            $manifest_filename,
            $load_config,
            $read_manifest
        );
    }

    /**
     * Check to see if there is a config file and if so, load it
     * @return void
     */
    public function loadConfig()
    {
        $config_file = join(DIRECTORY_SEPARATOR, [$this->plugin_path, 'asset-loader.config.json']);
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), TRUE);
            // if the config file exists but is invalid json, throw an error
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('DECODING the config file failed');
            }
        }
    }

    /**
     * checks to see if config is available for a script
     * @param  string $script  the script that has dependencies
     * @return array
     */
    public function getScriptConfig($script)
    {
        return isset($this->config[$script]) ? $this->config[$script] : [];
    }

    /**
     * gets the configuration
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Reads our manifest and finds the handles and the actual new files and saves
     * their association in assetMap
     * @return void
     */
    protected function readManifest()
    {
        $manifest = join(
            DIRECTORY_SEPARATOR,
            [
                $this->plugin_path,
                $this->distribution_path,
                $this->manifest_filename
            ]
        );

        if (file_exists($manifest)) {
            $manifest = json_decode(file_get_contents($manifest), TRUE);
            // throw an error if json decode failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('DECODING the manifest failed');
            }

            $this->asset_map = [];
            foreach ($manifest as $handle => $file) {
                $this->asset_map[$handle] = [
                    'url' => $this->makeDistributionFileUrl($file),
                    'file' => $file,
                    'full_path' => join(DIRECTORY_SEPARATOR, [$this->plugin_path, $this->distribution_path, $file])
                ];
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
    protected function makeDistributionFileUrl($filename = '')
    {
        return join('/', [$this->plugin_url, $this->distribution_path, $filename]);
    }

    /**
     * makes the script handle by applying our suffix
     * @param  string $script the string filename of the script
     * @return string
     */
    protected function prefixHandle($script = '')
    {
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
    public function registerAssets($enqueue = true)
    {
        // if the asset map is empty, the manifest may not have been read yet
        if (empty($this->asset_map)) {
            $this->readManifest();
        }

        foreach ($this->asset_map as $script => $data) {
            $handle = $this->prefixHandle($script);
            $url = $data['url'];
            $file = $data['file'];

            $config = $this->getScriptConfig($script);

            $dependencies = $this->script_deps;
            if (isset($config['dependencies']) && is_array($config['dependencies'])) {
                $dependencies = $config['dependencies'];
            }

            $version = $this->version;
            if (isset($config['version']) && is_string($config['version'])) {
                $version = $config['version'];
            }

            // put js in registeredAssets and use wp_register_script
            if (strpos($file, '.js') !== false) {
                $in_footer = false;
                if (isset($config["in_footer"]) && is_bool($config["in_footer"])) {
                    $in_footer = $config["in_footer"];
                }

                \wp_register_script($handle, $url, $dependencies, $version, $in_footer);
                $this->registered_assets[] = $handle;
                continue;
            }

            $media = 'all';
            if (isset($config['media']) && is_string($config['version'])) {
                $media = $config['media'];
            }

            // put css in registeredStyleAssets and use wp_register_script
            if (strpos($file, '.css') !== false) {
                \wp_register_style($handle, $url, $dependencies, $version, $media);
                $this->registered_style_assets[] = $handle;
            }
        }

        if ($enqueue === true) {
            $this->enqueueAssets();
        }
    }

    /**
     * Retrieves the full path for an asset
     * @param  string $script the script manifest key - i.e. main.js
     * @return string
     */
    public function assetPath($script)
    {
        return $this->getScriptData($script, 'full_path');
    }

    /**
     * retrieves the derrived url for an asset
     * @param  string $script the script manifest key - i.e. main.js
     * @return string
     */
    public function assetURL($script)
    {
        return $this->getScriptData($script, 'url');
    }

    /**
     * gets data from our asset map for a script
     * @param  string $script the script manifest key - i.e. main.js
     * @param  string $key    the data key - i.e. full_path or url
     * @return mixed           If key is empty, it will retrieve the assoc array. If the script data can't be found it will return false
     */
    public function getScriptData($script, $key = null)
    {
        if (!isset($this->asset_map[$script])) {
            return false;
        }

        if (empty($key)) {
            return $this->asset_map[$script];
        }

        if (isset($this->asset_map[$script][$key])) {
            return $this->asset_map[$script][$key];
        }

        return false;
    }

    /**
     * Enqueues all of the assets registered
     * @return [type] [description]
     */
    public function enqueueAssets()
    {
        foreach ($this->registered_assets as $handle) {
            \wp_enqueue_script($handle);
        }

        foreach ($this->registered_style_assets as $handle) {
            \wp_enqueue_style($handle);
        }
    }

    /* GETTER AND SETTER FUNCTIONS */
    /**
     * Retrieves the tag prefix
     * @return string
     */
    public function getTagPrefix()
    {
        return $this->tag_prefix;
    }

    /**
     * Retreives the version
     * @return string
     */
    public function getversion()
    {
        return $this->version;
    }

    /**
     * Retrieves the manifest filename
     * @return string
     */
    public function getManifestFilename()
    {
        return $this->manifest_filename;
    }

    /**
     * Retrieves the distribution path
     * @return string
     */
    public function getDistributionPath()
    {
        return $this->distribution_path;
    }

    /**
     * Retrieves the plugin path
     * @return string
     */
    public function getPluginPath()
    {
        return $this->plugin_path;
    }

    /**
     * Retrieves the instance plugin url
     * @return string
     */
    public function getPluginUrl()
    {
        return $this->plugin_url;
    }

    /**
     * Sets the tag prefix for handles
     * @param string $tag_prefix
     * @return  self
     */
    public function setTagPrefix($tag_prefix)
    {
        $this->tag_prefix = $tag_prefix;
        return $this;
    }

    /**
     * Sets the default version given to asll assets - userful for cache busting
     * @param string $version
     * @return  self
     */
    public function setVersion($version = '')
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Sets the manifest file name - i.e. manifest.json
     * @param string $manifest_filename
     * @return  self
     */
    public function setManifestFilename($manifest_filename = '')
    {
        $this->manifest_filename = $manifest_filename;
        return $this;
    }

    /**
     * Sets the distribution path - the place in this project from the "plugin_path"
     * that the asset files can be found. i.e. 'dist'
     * @param string $distribution_path [description]
     * @return  $this
     */
    public function setDistributionPath($distribution_path = '')
    {
        $this->distribution_path = $distribution_path;
        return $this;
    }

    /**
     * sets the root url to the project
     * @param string $plugin_url
     * @return  self
     */
    public function setPluginUrl($plugin_url = '')
    {
        $this->plugin_url = $plugin_url;
        return $this;
    }

    /**
     * Sets the root path for the project
     * @param string $plugin_path
     */
    public function setPluginPath($plugin_path = '')
    {
        $this->plugin_path = $plugin_path;
        return $this;
    }
}
