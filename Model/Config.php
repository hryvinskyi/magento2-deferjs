<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the module's settings for the current store view.
 *
 * The three exclusion lists are stored by the admin's dynamic-rows field as a JSON object of rows keyed by row id,
 * each row an object holding one column; they are read back as the plain list of that column's values.
 */
class Config implements ConfigInterface
{
    private const XML_PATH_ENABLED = 'hryvinskyi_defer_js/general/enabled';
    private const XML_PATH_DISABLE_ATTRIBUTE = 'hryvinskyi_defer_js/general/disable_attribute';
    private const XML_PATH_MINIFY_BODY_SCRIPTS = 'hryvinskyi_defer_js/general/minify_body_scripts';
    private const XML_PATH_MERGE_X_MAGENTO_INIT = 'hryvinskyi_defer_js/general/optimize_x_magento_init_scripts';
    private const XML_PATH_EXCLUDE_CONTROLLERS = 'hryvinskyi_defer_js/general/exclude_controllers';
    private const XML_PATH_EXCLUDE_PATHS = 'hryvinskyi_defer_js/general/exclude_paths';
    private const XML_PATH_EXCLUDE_URL_PATTERN = 'hryvinskyi_defer_js/general/exclude_url_pattern';

    /**
     * @param ScopeConfigInterface $scopeConfig the store configuration
     * @param SerializerInterface $serializer decodes the JSON the exclusion lists are stored as
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getDisableAttribute(): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_DISABLE_ATTRIBUTE, ScopeInterface::SCOPE_STORE);

        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * @inheritDoc
     */
    public function isMinifyBodyScripts(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_MINIFY_BODY_SCRIPTS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function isMergeXMagentoInit(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_MERGE_X_MAGENTO_INIT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getExcludedControllers(): array
    {
        return $this->getColumn(self::XML_PATH_EXCLUDE_CONTROLLERS, 'controller');
    }

    /**
     * @inheritDoc
     */
    public function getExcludedPaths(): array
    {
        return $this->getColumn(self::XML_PATH_EXCLUDE_PATHS, 'path');
    }

    /**
     * @inheritDoc
     */
    public function getExcludedUrlPatterns(): array
    {
        return $this->getColumn(self::XML_PATH_EXCLUDE_URL_PATTERN, 'pattern');
    }

    /**
     * Read one column of a stored list of rows.
     *
     * A value that is not JSON, or JSON that is not an object or array of rows, yields an empty list. Rows that
     * are not objects, lack the column, or hold anything but a string that is non-empty once trimmed are skipped.
     *
     * @param string $path the configuration path of the list
     * @param string $column the row key holding the value
     * @return list<string> the trimmed values, in row order
     */
    private function getColumn(string $path, string $column): array
    {
        $stored = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
        if (!is_string($stored) || trim($stored) === '') {
            return [];
        }

        try {
            $rows = $this->serializer->unserialize($stored);
        } catch (\InvalidArgumentException) {
            return [];
        }

        if (!is_array($rows)) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            $value = is_array($row) ? ($row[$column] ?? null) : null;
            if (!is_string($value) || trim($value) === '') {
                continue;
            }
            $values[] = trim($value);
        }

        return $values;
    }
}
