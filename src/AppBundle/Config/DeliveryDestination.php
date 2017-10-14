<?php


namespace AppBundle\Config;


class DeliveryDestination
{
    const SOURCE_PAYMENTS     = 'payments';
    const SOURCE_OPERATIONS   = 'operations';
    const SOURCE_EFFECTS      = 'effects';
    const SOURCE_LEDGERS      = 'ledgers';
    const SOURCE_TRANSACTIONS = 'transactions';

    /**
     * Source of the events, see SOURCE_ constants
     *
     * @var string
     */
    protected $source;

    /**
     * URL of the webhook that will be called
     *
     * @var string
     */
    protected $targetUrl;

    /**
     * Array of filter specifications
     *
     * Each object has keys:
     *  type: currently supported value: include
     *  field: the field within the json to filter on
     *
     *  ifValue: filter passes if field has this value
     *
     * @var array
     */
    protected $filters;

    /**
     * @param $rawData
     * @return DeliveryDestination
     * @throws \ErrorException
     */
    public static function fromRawData($rawData)
    {
        if (!isset($rawData['source'])) throw new \ErrorException('"source" is required');
        if (!isset($rawData['destination'])) throw new \ErrorException('"destination" is required');

        $object = new DeliveryDestination();
        $object->source = $rawData['source'];
        $object->targetUrl = $rawData['destination'];

        if (isset($rawData['filters'])) {
            $object->filters = $rawData['filters'];
        }

        return $object;
    }

    public function __construct()
    {
        $this->filters = [];
    }

    /**
     * Returns true if the raw data passes all filters and the webhook should be
     * fired
     *
     * @param $rawData
     * @return bool
     */
    public function shouldFireForRawData($rawData)
    {
        // No filters mean that we always match and send the webhook
        if (!$this->filters) return true;

        foreach ($this->filters as $filter) {
            if ($this->rawDataPassesFilter($rawData, $filter)) return true;
        }

        return false;
    }

    /**
     * @param $rawData
     * @param $filter
     * @return bool
     */
    protected function rawDataPassesFilter($rawData, $filter)
    {
        // Throws an exception if validation fails
        $this->validateFilter($filter);

        $testField = $filter['field'];
        isset($rawData[$testField]) ?  $testValue = $rawData[$testField] : $testValue = null;

        if (array_key_exists('ifValue', $filter)) {
            if ($testValue == $filter['ifValue']) {
                if ('include' == $filter['type']) return true;
            }
        }

        return false;
    }

    /**
     * @param $filter
     */
    protected function validateFilter($filter)
    {
        // Validate filter types
        $validTypes = ['include'];
        if (!in_array($filter['type'], $validTypes)) throw new \InvalidArgumentException(sprintf('Invalid filter type "%s". Valid types: %s', $filter['type'], join(',', $validTypes)));
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    /**
     * @param string $targetUrl
     */
    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }
}