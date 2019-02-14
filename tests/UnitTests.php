<?php

use PHPUnit\Framework\TestCase;
use MANIFEST_LOADER\AssetLoader;

class UnitTests extends TestCase
{

    protected $plugin_url = 'https://example.com';
    protected $plugin_path = '';
    protected $distribution_path = 'dist';
    protected $manifest_filename = 'manifest.json';
    protected $version = '2.5';
    protected $tag_prefix = 'xkcd';

    protected function setUp()
    {
        $this->plugin_path = join(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'testApp']);
    }

    /**
     * This will test the creation of our loader
     */
    public function testCreateLoader()
    {
        $loader = $this->createLoader();
        $this->assertTrue($loader instanceof AssetLoader);
    }

    /**
     * this will test most if not all of the classes setters and getters
     */
    public function testSettersAndGetters()
    {
        $loader = $this->createLoader();
        $this->_test_variable_set($loader, 'http://something.org', 'setPluginUrl', 'getPluginUrl');
        $this->_test_variable_set($loader, '/var/www/', 'setPluginPath', 'getPluginPath');
        $this->_test_variable_set($loader, 'lib', 'setDistributionPath', 'getDistributionPath');
        $this->_test_variable_set($loader, 'assets,json', 'setManifestFilename', 'getManifestFilename');
        $this->_test_variable_set($loader, '2.8', 'setVersion', 'getVersion');
        $this->_test_variable_set($loader, 'hello-mamma', 'setTagPrefix', 'getTagPrefix');
    }

    /**
     * this will test that our config is getting loaded correctly
     */
    public function testLoadConfig()
    {
        $loader = $this->createLoader();

        $loader->loadConfig();
        $config = $loader->getConfig();
        $this->assertTrue(!empty($config), "Failed asserting that the config is not empty from " . $loader->getPluginPath());

        $script_config = $loader->getScriptConfig('main.js');
        $this->assertTrue(!empty($script_config), "Failed asserting that the script config is not empty");

        $version = $script_config["version"];
        $this->assertEquals($version, "0.1", "Failed asserting that $version is 0.1");
    }

    /**
     * test our manifest is created correctly
     */
    public function testManifest()
    {
        $loader = $this->createLoader();
        $asset_path = $loader->assetPath('main.js');
        $guess_path = join(DIRECTORY_SEPARATOR, [$this->plugin_path, $this->distribution_path, 'main-a80c136812df324d6bcf.js']);
        $this->assertEquals($guess_path, $asset_path, "Failed to asset the right path. We think it should be $guess_path but we got  " . $asset_path . ' instead');

        $asset_url = $loader->assetURL('main.js');
        $guess_url = join('/', [$this->plugin_url, $this->distribution_path, 'main-a80c136812df324d6bcf.js']);
        $this->assertEquals($guess_url, $asset_url, "Failed to asset the right path. We think it should be $guess_url but we got  " . $asset_url . ' instead');
    }

    protected function _test_variable_set($loader, $value, $setter, $getter)
    {
        $loader->$setter($value);
        $this->assertEquals($loader->$getter(), $value);
    }

    protected function createLoader()
    {
        return AssetLoader::create(
            $this->plugin_url,
            $this->plugin_path,
            $this->tag_prefix,
            $this->version,
            $this->distribution_path,
            $this->manifest_filename,
            true
        );
    }
}
