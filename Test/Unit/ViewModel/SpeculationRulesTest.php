<?php

namespace MageOS\ThemeOptimization\Test\Unit\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\ThemeOptimization\ViewModel\SpeculationRules;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpeculationRulesTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var SpeculationRules
     */
    private $speculationRules;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->speculationRules = new SpeculationRules(
            $this->scopeConfigMock,
            $this->urlBuilderMock,
            $this->serializerMock
        );
    }

    // Test Category #1: Constructor and Dependencies Tests

    public function testConstructorInjectsDependencies(): void
    {
        $this->assertInstanceOf(SpeculationRules::class, $this->speculationRules);
    }

    public function testImplementsArgumentInterface(): void
    {
        $this->assertInstanceOf(ArgumentInterface::class, $this->speculationRules);
    }

    // Test Category #2: Configuration Tests - isEnabled() method

    /**
     * @dataProvider enabledDataProvider
     */
    public function testIsEnabled($configValue, bool $expected): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn($configValue);

        $result = $this->speculationRules->isEnabled();
        $this->assertEquals($expected, $result);
    }

    public function enabledDataProvider(): array
    {
        return [
            'string true' => ['1', true],
            'string false' => ['0', false],
            'boolean true' => [true, true],
            'boolean false' => [false, false],
            'null' => [null, false],
            'empty string' => ['', false],
            'non-empty string' => ['enabled', true],
            'integer 1' => [1, true],
            'integer 0' => [0, false],
        ];
    }

    // Test Category #3: Eagerness Mode Tests

    /**
     * @dataProvider eagernessDataProvider
     */
    public function testGetEagerness($configValue, string $expected): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/eagerness', ScopeInterface::SCOPE_STORE)
            ->willReturn($configValue);

        $result = $this->speculationRules->getEagerness();
        $this->assertEquals($expected, $result);
    }

    public function eagernessDataProvider(): array
    {
        return [
            'conservative' => ['conservative', 'conservative'],
            'moderate' => ['moderate', 'moderate'],
            'eager' => ['eager', 'eager'],
            'invalid mode' => ['invalid', 'moderate'],
            'null' => [null, 'moderate'],
            'empty string' => ['', 'moderate'],
            'case sensitive - Conservative' => ['Conservative', 'moderate'],
            'case sensitive - MODERATE' => ['MODERATE', 'moderate'],
        ];
    }

    // Test Category #4: Speculation Rules Generation Tests

    public function testGetSpeculationRulesStructure(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'moderate',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $result = $this->speculationRules->getSpeculationRules();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prerender', $result);
        $this->assertIsArray($result['prerender']);
        $this->assertCount(1, $result['prerender']);

        $prerenderRule = $result['prerender'][0];
        $this->assertArrayHasKey('source', $prerenderRule);
        $this->assertArrayHasKey('where', $prerenderRule);
        $this->assertArrayHasKey('eagerness', $prerenderRule);

        $this->assertEquals('document', $prerenderRule['source']);
        $this->assertEquals('moderate', $prerenderRule['eagerness']);
        $this->assertIsArray($prerenderRule['where']);
    }

    public function testGetSpeculationRulesWithDifferentEagerness(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'eager',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $result = $this->speculationRules->getSpeculationRules();
        $this->assertEquals('eager', $result['prerender'][0]['eagerness']);
    }

    // Test Category #5: JSON Serialization Tests

    public function testGetSpeculationRulesJson(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'moderate',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $expectedJson = '{"prerender":[{"source":"document","where":{},"eagerness":"moderate"}]}';

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->willReturn($expectedJson);

        $result = $this->speculationRules->getSpeculationRulesJson();
        $this->assertEquals($expectedJson, $result);
    }

    public function testGetSpeculationRulesJsonCallsSerializer(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'conservative',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function ($rules) {
                return is_array($rules) &&
                    isset($rules['prerender']) &&
                    is_array($rules['prerender']);
            }))
            ->willReturn('{"serialized":"data"}');

        $result = $this->speculationRules->getSpeculationRulesJson();
        $this->assertEquals('{"serialized":"data"}', $result);
    }

    // Test Category #6: Rules Building Tests (tested via public methods)

    public function testBuildRulesIncludesDefaultPattern(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'moderate',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $result = $this->speculationRules->getSpeculationRules();
        $where = $result['prerender'][0]['where'];

        $this->assertArrayHasKey('and', $where);
        $this->assertContains(['href_matches' => '/*'], $where['and']);
    }

    public function testBuildRulesIncludesHardcodedExclusions(): void
    {
        $this->setupConfigMocks([
            'eagerness' => 'moderate',
            'exclude_paths' => '',
            'exclude_extensions' => '',
            'exclude_selectors' => ''
        ]);

        $result = $this->speculationRules->getSpeculationRules();
        $where = $result['prerender'][0]['where'];

        $expectedExclusions = [
            ['not' => ['selector_matches' => '[rel=nofollow]']],
            ['not' => ['selector_matches' => '[target=_blank]']],
            ['not' => ['selector_matches' => '[target=_parent]']],
            ['not' => ['selector_matches' => '[target=_top]']],
        ];

        foreach ($expectedExclusions as $exclusion) {
            $this->assertContains($exclusion, $where['and']);
        }
    }

    // Test Category #7: Excluded Paths Tests

    public function testGetExcludedPathsSinglePath(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE)
            ->willReturn('testPath');

        $result = $this->speculationRules->getExcludedPaths();

        $expected = ['not' => ['href_matches' => '/*(testPath)/*']];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedPathsMultiplePaths(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE)
            ->willReturn("admin\napi\ncheckout");

        $result = $this->speculationRules->getExcludedPaths();

        $expected = ['not' => ['href_matches' => '/*(admin|api|checkout)/*']];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedPathsTrimsSlashes(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE)
            ->willReturn(" /admin/ \n /api/ ");

        $result = $this->speculationRules->getExcludedPaths();

        $expected = ['not' => ['href_matches' => '/*(admin|api)/*']];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedPathsFiltersEmpty(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE)
            ->willReturn("admin\n\napi\n   \ncheckout");

        $result = $this->speculationRules->getExcludedPaths();

        $expected = ['not' => ['href_matches' => '/*(admin|api|checkout)/*']];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedPathsEmptyConfig(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $result = $this->speculationRules->getExcludedPaths();

        $expected = [];
        $this->assertEquals($expected, $result);
    }

    // Test Category #8: Excluded Extensions Tests

    public function testGetExcludedExtensionsSingle(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE)
            ->willReturn('pdf');

        $result = $this->speculationRules->getExcludedExtensions();

        $expected = [['not' => ['href_matches' => '*.pdf']]];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedExtensionsMultiple(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE)
            ->willReturn('pdf,doc,zip');

        $result = $this->speculationRules->getExcludedExtensions();

        $expected = [
            ['not' => ['href_matches' => '*.pdf']],
            ['not' => ['href_matches' => '*.doc']],
            ['not' => ['href_matches' => '*.zip']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedExtensionsTrimsAndFilters(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE)
            ->willReturn(' pdf , , doc ,  zip ');

        $result = $this->speculationRules->getExcludedExtensions();

        $expected = [
            ['not' => ['href_matches' => '*.pdf']],
            ['not' => ['href_matches' => '*.doc']],
            ['not' => ['href_matches' => '*.zip']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedExtensionsRemovesLeadingDots(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE)
            ->willReturn('.pdf,doc,.zip');

        $result = $this->speculationRules->getExcludedExtensions();

        $expected = [
            ['not' => ['href_matches' => '*.pdf']],
            ['not' => ['href_matches' => '*.doc']],
            ['not' => ['href_matches' => '*.zip']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedExtensionsEmptyConfig(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $result = $this->speculationRules->getExcludedExtensions();
        $this->assertEquals([], $result);
    }

    // Test Category #9: Excluded Selectors Tests

    public function testGetExcludedSelectorsSingle(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE)
            ->willReturn('.no-prerender');

        $result = $this->speculationRules->getExcludedSelectors();

        $expected = [['not' => ['selector_matches' => '.no-prerender']]];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedSelectorsMultiple(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE)
            ->willReturn(".no-prerender\n#skip-prerender\n[data-no-prerender]");

        $result = $this->speculationRules->getExcludedSelectors();

        $expected = [
            ['not' => ['selector_matches' => '.no-prerender']],
            ['not' => ['selector_matches' => '#skip-prerender']],
            ['not' => ['selector_matches' => '[data-no-prerender]']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedSelectorsTrimsAndFilters(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE)
            ->willReturn(" .no-prerender \n\n #skip-prerender \n   \n[data-no-prerender]");

        $result = $this->speculationRules->getExcludedSelectors();

        $expected = [
            ['not' => ['selector_matches' => '.no-prerender']],
            ['not' => ['selector_matches' => '#skip-prerender']],
            ['not' => ['selector_matches' => '[data-no-prerender]']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedSelectorsComplexSelectors(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE)
            ->willReturn("a.btn:not(.prerender)\n.modal a[href*=\"logout\"]");

        $result = $this->speculationRules->getExcludedSelectors();

        $expected = [
            ['not' => ['selector_matches' => 'a.btn:not(.prerender)']],
            ['not' => ['selector_matches' => '.modal a[href*="logout"]']],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedSelectorsEmptyConfig(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $result = $this->speculationRules->getExcludedSelectors();
        $this->assertEquals([], $result);
    }

    // Test Category #10: Configuration Path Tests

    public function testConfigPathUsage(): void
    {
        $configCalls = [
            ['dev/speculation_rules/enabled', ScopeInterface::SCOPE_STORE],
            ['dev/speculation_rules/eagerness', ScopeInterface::SCOPE_STORE],
            ['dev/speculation_rules/exclude_paths', ScopeInterface::SCOPE_STORE],
            ['dev/speculation_rules/exclude_extensions', ScopeInterface::SCOPE_STORE],
            ['dev/speculation_rules/exclude_selectors', ScopeInterface::SCOPE_STORE],
        ];

        $this->scopeConfigMock->expects($this->exactly(5))
            ->method('getValue')
            ->willReturnCallback(function ($path, $scope) use (&$configCalls) {
                $expectedCall = array_shift($configCalls);
                $this->assertEquals($expectedCall[0], $path);
                $this->assertEquals($expectedCall[1], $scope);
                return '';
            });

        // Call methods that trigger config reads
        $this->speculationRules->isEnabled();
        $this->speculationRules->getEagerness();
        $this->speculationRules->getExcludedPaths();
        $this->speculationRules->getExcludedExtensions();
        $this->speculationRules->getExcludedSelectors();
    }

    /**
     * Helper method to setup multiple config mock calls
     */
    private function setupConfigMocks(array $configValues): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function ($path) use ($configValues) {
                $key = str_replace('dev/speculation_rules/', '', $path);
                return $configValues[$key] ?? null;
            });
    }
}
