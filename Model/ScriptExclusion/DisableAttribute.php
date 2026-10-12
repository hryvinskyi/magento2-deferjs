<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\ScriptExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Keeps in place a script tag whose text contains the configured disable attribute, compared case-insensitively.
 *
 * With no attribute configured nothing is kept. The attribute is asked once per store view and remembered for the
 * lifetime of this object: a page is checked script by script, and reading the setting for every tag costs more
 * than the check itself. The current store is looked up on every call, so a switch of store view (an emulated
 * store, for instance) reads that store's own setting.
 */
class DisableAttribute implements ScriptExclusionInterface
{
    /**
     * The configured attribute, keyed by the id of the store view it was read for.
     *
     * @var array<int, string>
     */
    private array $attributes = [];

    /**
     * @param ConfigInterface $config the module's settings
     * @param StoreManagerInterface $storeManager tells which store view the settings are read for
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException when the current store view cannot be resolved
     */
    public function isExcluded(string $script): bool
    {
        $attribute = $this->getAttribute();

        return $attribute !== '' && stripos($script, $attribute) !== false;
    }

    /**
     * The disable attribute of the current store view, read from the settings the first time that store asks.
     *
     * @return string the trimmed attribute, empty when none is configured
     * @throws NoSuchEntityException when the current store view cannot be resolved
     */
    private function getAttribute(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return $this->attributes[$storeId] ??= $this->config->getDisableAttribute();
    }
}
