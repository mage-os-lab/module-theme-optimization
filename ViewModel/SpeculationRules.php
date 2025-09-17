<?php

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class SpeculationRules implements ArgumentInterface
{
    protected const CONFIG_PATH = 'system/speculation_rules/';
    protected const FETCH_MODES = ['prefetch', 'prerender'];
    protected const EAGERNESS_MODES = ['conservative', 'moderate', 'eager'];

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected UrlInterface         $urlBuilder,
        protected SerializerInterface  $serializer,
    )
    {
    }

    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue('enabled');
    }

    public function getMode(): string
    {
        $mode = $this->getConfigValue('mode');

        if (in_array($mode, self::FETCH_MODES, true)) {
            return $mode;
        }

        return 'prefetch';
    }

    public function getEagerness(): string
    {
        $eagerness = $this->getConfigValue('eagerness');

        if (in_array($eagerness, self::EAGERNESS_MODES, true)) {
            return $eagerness;
        }

        return 'moderate';
    }

    public function getSpeculationRules(): array
    {
        // Possible future development: add support for multiple modes and rulesets at once.
        return [
            $this->getMode() => [
                [
                    'source' => 'document',
                    'where' => $this->buildRules(),
                    'eagerness' => $this->getEagerness(),
                ],
            ],
        ];
    }

    public function getSpeculationRulesJson(): string
    {
        $rules = $this->getSpeculationRules();

        return $this->serializer->serialize($rules);
    }

    protected function buildRules(): array
    {
        // Include all URLs by default
        $rules = [
            'and' => [
                ['href_matches' => '/*']
            ],
        ];

        // Exclude path patterns (wildcards)
        $rules['and'][] = $this->getExcludedPaths();

        // Exclude file extensions
        array_push($rules['and'], ...$this->getExcludedExtensions());

        // Exclude selectors
        array_push($rules['and'], ...$this->getExcludedSelectors());

        // TODO: Add extensibility?

        // Always exclude common unsafe targets
        $rules['and'][] = ['not' => ['selector_matches' => '[rel=nofollow]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_blank]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_parent]']];
        $rules['and'][] = ['not' => ['selector_matches' => '[target=_top]']];

        return $rules;
    }

    protected function getConfigValue(string $key): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getExcludedPaths(): array
    {
        $paths = explode("\n", (string)$this->getConfigValue('exclude_paths'));

        foreach ($paths as &$pattern) {
            $pattern = trim(trim($pattern), '/');
        }
        $paths = array_filter($paths);

        if (empty($paths)) {
            return [];
        }

        return ['not' => ['href_matches' => '/*(' . implode('|', $paths) . ')/*']];
    }

    public function getExcludedExtensions(): array
    {
        $rules = [];

        $extensions = explode(',', (string)$this->getConfigValue('exclude_extensions'));
        $extensions = array_filter(array_map('trim', $extensions));
        foreach ($extensions as $extension) {
            $rules[] = ['not' => ['href_matches' => sprintf('*.%s', ltrim($extension, '.'))]];
        }

        return $rules;
    }

    public function getExcludedSelectors(): array
    {
        $rules = [];

        $selectors = explode("\n", (string)$this->getConfigValue('exclude_selectors'));
        $selectors = array_filter(array_map('trim', $selectors));
        foreach ($selectors as $selector) {
            $rules[] = ['not' => ['selector_matches' => $selector]];
        }

        return $rules;
    }
}
