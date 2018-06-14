<?php

namespace Symbiote\DesignBridge;

use Debug;
use Controller;
use SS_HTTPRequest;
use SiteConfig;
use SS_TemplateLoader;
use SSViewer;
use Config;
use Exception;
use ArrayData;
use ArrayList;
use Director;
use Member;
use HTMLText;
use Permission;
use Injector;
use Page_Controller;

class DesignBridgeController extends Page_Controller
{
    /**
     * The URL segment to use for the component listing page.
     * We're using an underscore to avoid clashing with a client page.
     *
     * @config
     * @var string
     */
    private static $url_segment = '_components';

    /**
     * @config
     * @var string|array
     */
    private static $required_permission_codes = 'ADMIN';

    /**
     * @config
     * @var array
     */
    private static $allowed_actions = array(
        'index',
        'all',
        'formkitchensink',
        'view',
        'Form',
    );

    /**
     * @config
     * @var array
     */
    private static $url_handlers = [
        '' => 'index',
        'all' => 'all',
        'formkitchensink' => 'formkitchensink',
        'Form' => 'Form',
        '$ComponentName' => 'view',
    ];

    /**
     * @config
     * @var array
     */
    private static $component_groups = array(
        'atoms',
        'molecules',
        'organisms',
    );

    /**
     * @var array|null
     */
    protected $_component_templates_grouped_cache = null;

    public function init()
    {
        parent::init();
        $member = Member::currentUser();
        if (!$this->canView($member)) {
            if (Member::currentUser()) {
                Session::set("BackURL", null);
            }

            // if no alternate menu items have matched, return a permission error
            $messageSet = array(
                'default' => _t(
                    'LeftAndMain.PERMDEFAULT',
                    "You must be logged in to access the administration area; please enter your credentials below."
                ),
                'alreadyLoggedIn' => _t(
                    'LeftAndMain.PERMALREADY',
                    "I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do"
                    . " so below."
                ),
                'logInAgain' => _t(
                    'LeftAndMain.PERMAGAIN',
                    "You have been logged out of the CMS.  If you would like to log in again, enter a username and"
                    . " password below."
                ),
            );

            return Security::permissionFailure($this, $messageSet);
        }
    }

    /**
     * View the index page.
     *
     * @return HTMLText
     */
    public function index(SS_HTTPRequest $request)
    {
        $componentByGroup = $this->getComponentTemplatesGrouped();

        //
        $groups = array();
        foreach ($componentByGroup as $groupName => $unfilteredComponentList) {
            $componentList = array();
            foreach ($unfilteredComponentList as $component) {
                if ($component['IsDefaultParametersTemplate']) {
                    $componentList[] = $component;
                }
            }
            $groups[] = array(
                'Title' => ucfirst($groupName),
                'Items' => new ArrayList($componentList),
            );
        }
        $groups = new ArrayList($groups);

        $result = $this->customise(array(
            'Title' => 'Components',
            'Groups' => $groups,
        ));
        return $result->renderWith(array(
            'DesignBridgeController_index',
            'DesignBridgeController',
            'Page',
        ));
    }

    /**
     * View a component
     *
     * @return HTMLText
     */
    public function formkitchensink(SS_HTTPRequest $request)
    {
        $result = $this->customise(array(
            'Title' => 'Form Kitchensink',
        ));
        return $this->renderWith(array(
            'DesignBridgeController_form',
            'DesignBridgeController',
            'Page',
        ));
    }

    /**
     * View a component
     *
     * @return HTMLText
     */
    public function view(SS_HTTPRequest $request)
    {
        $componentName = $request->param('ComponentName');
        $componentByGroup = $this->getComponentTemplatesGrouped();
        $components = array();
        foreach ($componentByGroup as $componentList) {
            foreach ($componentList as $component) {
                if ($component['Title'] === $componentName) {
                    $components[] = $component;
                }
            }
        }
        if (!$components) {
            return $this->httpError(404, 'Cannot find component: '.$componentName.', have you run ?flush=all?');
        }
        foreach ($components as $k => $component) {
            $components[$k] = array_merge($components[$k], $this->getRenderedComponentData($component));
        }
        $components = new ArrayList($components);
        $result = $this->customise(array(
            'Components' => $components,
        ));
        return $result->renderWith(array(
            'DesignBridgeController_view',
            'DesignBridgeController',
            'Page',
        ));
    }

    /**
     * Render all components with the 'ComponentHolder.ss' template wrapper.
     *
     * @return HTMLText
     */
    public function all(SS_HTTPRequest $request)
    {
        $componentByGroup = $this->getComponentTemplatesGrouped();
        $components = array();
        foreach ($componentByGroup as $componentList) {
            foreach ($componentList as $component) {
                if (!$component['IsDefaultParametersTemplate']) {
                    $components[] = $component;
                }
            }
        }
        if (!$components) {
            return $this->httpError(404, 'You do not have any components. (Remember to ?flush=all after creating a new template)');
        }
        foreach ($components as $k => $component) {
            $components[$k] = array_merge($components[$k], $this->getRenderedComponentData($component));
        }
        $components = new ArrayList($components);

        $result = $this->customise(array(
            'Title' => 'All Components',
            'Components' => $components,
        ));
        return $result->renderWith(array(
            'DesignBridgeController_all',
            'DesignBridgeController',
            'Page',
        ));
    }

    /**
     * @return array
     */
    protected function getRenderedComponentData(array $componentProps)
    {
        $componentName = $componentProps['Title'];
        //$componentProps = array(
        //    '_ComponentInfo' => $componentProps,
        //);

        $service = Injector::inst()->get('Symbiote\\DesignBridge\\ComponentHolderService');
        $componentHolderHTML = $service->renderComponentTopLevel($componentName, $componentProps);

        return array(
            'Component' => $componentHolderHTML->Component,
            'ComponentHolder' => $componentHolderHTML,
        );
    }

    /**
     * @return array
     */
    protected function getComponentTemplatesGrouped()
    {
        if ($this->_component_templates_grouped_cache !== null) {
            return $this->_component_templates_grouped_cache;
        }
        $theme = $this->getTheme();
        $templateManifest = SS_TemplateLoader::instance()->getManifest();
        $componentFolders = $this->config()->component_groups;
        if (!$componentFolders) {
            throw new Exception('DesignBridgeController::component_groups has zero items configured.');
        }
        $componentByGroup = array();
        foreach ($componentFolders as $componentGroupName) {
            $componentByGroup[$componentGroupName] = array();
        }
        foreach ($templateManifest->getTemplates() as $templateLookupName => $templateInfo) {
            foreach ($componentFolders as $componentGroupName) {
                $groupName = ucfirst($componentGroupName);
                foreach (array($componentGroupName, $componentGroupName.'_examples') as $componentFolderName) {
                    if (!isset($templateInfo['themes'][$theme][$componentFolderName])) {
                        continue;
                    }
                    $templatePathname = $templateInfo['themes'][$theme][$componentFolderName];
                    $templateName = basename($templatePathname, '.ss');
                    $sketchSymbol = $groupName.'/'.$templateName;
                    $componentByGroup[$componentGroupName][] = array(
                        'Title' => $templateName,        // ie "Heading1"
                        'GroupName' => $groupName,       // ie: "Atoms", "Molecules"
                        'Name' => $templateLookupName,   // ie. "heading1"
                        'ComponentHolder' => '',         // ie. Stub for ComponentHolder.ss template
                        'Component' => '',               // ie. Stub for renderWith() of component
                        'Link' => $this->Link($templateName), // Link to the component in this controller
                        'Pathname' => $templatePathname, // ie. Filepath to the template file
                        'IsDefaultParametersTemplate' => strpos($templateName, '_example') !== FALSE,
                    );
                }
            }
        }
        $this->_component_templates_grouped_cache = $componentByGroup;
        return $componentByGroup;
    }

    /**
     * @return FormKitchensink
     */
    public function Form()
    {
        return FormKitchensink::create($this, __FUNCTION__);
    }

    /**
     * @return string
     */
    protected function getTheme()
    {
        // If you're using the SiteConfig module, get theme information
        if (class_exists(SiteConfig::class)) {
            $config = SiteConfig::current_site_config();
            if ($config->Theme) {
                Config::inst()->update(SSViewer::class, 'theme_enabled', true);
                Config::inst()->update(SSViewer::class, 'theme', $config->Theme);
            }
        }
        // todo(Jake): 2018-03-23
        //
        // Add Multisites support
        //
        $theme = Config::inst()->get(SSViewer::class, 'theme');
        return $theme;
    }

    /**
     * @return string
     */
    public function Link($action = '')
    {
        return Controller::join_links(Director::baseURL(), $this->RelativeLink($action));
    }

    /**
     * @return string
     */
    public function RelativeLink($action = '')
    {
        $urlSegment = $this->config()->url_segment;
        return Controller::join_links(
            $urlSegment,
            $action
        );
    }

    /**
     * @return boolean|null
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        $requiredPermissionCodes = $this->config()->required_permission_codes;
        if ($member &&
            $requiredPermissionCodes &&
            Permission::check($requiredPermissionCodes, 'any', $member)) {
            return true;
        }
        if (Director::isDev()) {
            return true;
        }
        return false;
    }
}
