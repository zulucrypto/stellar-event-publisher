<?php


namespace AppBundle\Config;


class MonitorConfig
{
    /**
     * Array of sources to look in for config data
     *
     * This is an array of objects with keys:
     *  type (string) one of: directory, file, url
     *  path (string)
     *
     * @var array
     */
    protected $sources;

    /**
     * @var DeliveryDestination[]
     */
    protected $destinations;

    public function __construct()
    {
        $this->sources = [];
        $this->destinations = [];
    }

    /**
     * Returns true if there are no sources or no destinations
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (count($this->sources) === 0) return true;
        if (count($this->destinations) === 0) return true;

        return false;
    }

    /**
     * @param $type
     * @param $path
     */
    public function addSource($type, $path)
    {
        $validTypes = ['directory', 'file', 'url'];
        if (!in_array($type, $validTypes)) throw new \InvalidArgumentException(sprintf('Invalid type, must be one of %s', join(',', $validTypes)));

        if ($this->hasSource($type, $path)) return;

        $this->sources[] = ['type' => $type, 'path' => $path];
    }

    /**
     * @param $type
     * @param $path
     * @return bool
     */
    public function hasSource($type, $path)
    {
        foreach ($this->sources as $source) {
            if ($source['type'] == $type && $source['path'] == $path) return true;
        }

        return false;
    }

    /**
     * Returns true if there's a delivery destination for $source
     *
     * @param $source string one of the SOURCE_ constants in DeliveryDestination
     * @return bool
     */
    public function hasDestinationForSource($source)
    {
        foreach ($this->destinations as $destination) {
            if ($destination->getSource() == $source) return true;
        }

        return false;
    }

    /**
     * @param $testSource string one of the SOURCE_ constants in DeliveryDestination
     * @return DeliveryDestination[]|array
     */
    public function getDestinationsForSource($testSource)
    {
        return array_filter($this->destinations, function($destination) use ($testSource) {
            return $destination->getSource() == $testSource;
        });
    }

    /**
     * Processes configuration from all configured sources
     */
    public function load()
    {
        foreach ($this->sources as $source) {
            if ($source['type'] == 'directory') {
                $this->loadFromDirectory($source['path']);
            }
            if ($source['type'] == 'file') {
                $this->loadFromFile($source['path']);
            }
            // file_get_contents handles urls, so re-use this method
            if ($source['type'] == 'url') {
                $this->loadFromFile($source['path']);
            }
        }
    }

    /**
     * Loads all .json files in the specified directory
     *
     * @param $directoryPath
     */
    protected function loadFromDirectory($directoryPath)
    {
        $files = scandir($directoryPath);
        if (!$files) return;

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;

            // Only consider files that end in .json
            if (substr($file, -5) != '.json') continue;

            $filePath = realpath($directoryPath . DIRECTORY_SEPARATOR . $file);

            $this->loadFromFile($filePath);
        }
    }

    /**
     * Parses a single file to extract multiple delivery destinations and add
     * them to the config
     *
     * @param $filePath
     * @throws \ErrorException
     */
    protected function loadFromFile($filePath)
    {
        $contents = file_get_contents($filePath);
        if (!$contents) throw new \ErrorException('Could not read from ' . $filePath);

        // Remove any comments in the file
        $contents = preg_replace('/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $contents);

        // Parse as json
        $parsed = json_decode($contents, true);

        if ($parsed === null && json_last_error()) {
            throw new \ErrorException(sprintf('Could not parse %s: %s', $filePath, json_last_error_msg()));
        }

        foreach ($parsed as $rawDeliveryDestination) {
            $this->destinations[] = DeliveryDestination::fromRawData($rawDeliveryDestination);
        }
    }
}