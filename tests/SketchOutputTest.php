<?php

namespace SilbinaryWolf\DesignBridge\Tests;

use Config;
use SapphireTest;
use SSViewer;
use SSViewer_FromString;
use ArrayData;
use TextField;
use Injector;

class SketchOutputTest extends SapphireTest
{
    /**
     * This test ensures that nested sketch symbols is working
     */
    public function testGetRenderedComponentData()
    {
        $service = Injector::inst()->get('Symbiote\\DesignBridge\\ComponentHolderService');
        $props = array();
        $componentHolderHTML = $service->renderComponentTopLevel('MyComponentButtonHolder', $props);
        $resultHTML = $componentHolderHTML->getValue();
        $expectedHTML = <<<HTML
<div data-sketch-symbol="Components/MyComponentButtonHolder">
    <div class="MyComponentButton_holder">
        <div data-sketch-symbol="Components/MyComponentButton">
            <button class="test" type="button"></button>
        </div>
    </div>
</div>
HTML;
        $this->assertEqualIgnoringWhitespace($expectedHTML, $resultHTML, 'Unexpected output');
    }

    /**
     * Taken from "framework\tests\view\SSViewerTest.php"
     */
    protected function assertEqualIgnoringWhitespace($a, $b, $message = '')
    {
        $this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b), $message);
    }
}
