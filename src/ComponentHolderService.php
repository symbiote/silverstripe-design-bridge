<?php

namespace Symbiote\DesignBridge;

use Symbiote\Components\ComponentService;
use SSViewer;
use SSViewer_Scope;
use HTMLText;
use Injector;
use SS_TemplateLoader;
use Config;
use Exception;

class ComponentHolderService extends ComponentService
{
    public function renderComponentTopLevel($name, array $props)
    {
        Injector::nest();
        Injector::inst()->registerService(new ComponentHolderService, 'Symbiote\\Components\\ComponentService');
        $service = Injector::inst()->get('Symbiote\\Components\\ComponentService');
        $componentHolderHTML = $service->renderComponent($name, $props, new \SSViewer_Scope(null));
        Injector::unnest();
        return $componentHolderHTML;
    }

    public function renderComponent($name, array $props, SSViewer_Scope $scope)
    {
        // NOTE(Jake): 2018-04-30
        //
        // We're using Injector::unnest() to temporarily stop using the ComponentHolderService
        // for rendering and to use either the original class or a custom injector in the project.
        //
        // We do this so nested rendering of components can be reasoned about with Sketch.
        //
        Injector::unnest();
        $service = Injector::inst()->get('Symbiote\\Components\\ComponentService');
        Injector::nest();
        Injector::inst()->registerService(new ComponentHolderService, 'Symbiote\\Components\\ComponentService');

        if (get_class($service) === __CLASS__) {
            //$componentHTML = parent::renderComponent($name, $props, $scope);
            throw new Exception('Expected "Symbiote\\Components\\ComponentService" to be reverted back to the original Injector config before calling renderComponent(). This is so we can override ComponentService with the injector on a project to project basis if need be.');
        }
        $componentHTML = $service->renderComponent($name, $props, $scope);

        $props['SketchSymbol'] = $this->getSketchSymbol($name);
        $template = new SSViewer('ComponentHolder');
        $componentHolderHTML = $template->process(null, array_merge($props, array(
            'Component' => $componentHTML,
        )));
        $componentHolderHTML->Component = $componentHTML;
        return $componentHolderHTML;
    }

    /**
     * Return a sketch symbol value, ie. "Atoms/MyComponent"
     *
     * @param string $name
     * @return string
     */
    private function getSketchSymbol($name)
    {
        $folderToFilepath = SS_TemplateLoader::instance()->findTemplates(array($name), Config::inst()->get('SSViewer', 'theme'));
        reset($folderToFilepath);
        $folder = key($folderToFilepath);
        $filepath = $folderToFilepath[$folder];
        $name = basename($filepath, '.ss');
        $sketchSymbol = ucfirst($folder).'/'.$name;
        return $sketchSymbol;
    }
}
