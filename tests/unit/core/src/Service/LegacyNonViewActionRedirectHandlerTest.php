<?php

namespace App\Tests;

use App\Service\LegacyNonViewActionRedirectHandler;
use Codeception\Test\Unit;
use Exception;
use App\Legacy\ActionNameMapperHandler;
use App\Legacy\ModuleNameMapperHandler;
use App\Legacy\RouteConverterHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;

class LegacyNonViewActionRedirectHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var LegacyNonViewActionRedirectHandler
     */
    private $handler;

    /**
     * @throws Exception
     */
    protected function _before(): void
    {
        $projectDir = $this->tester->getProjectDir();
        $legacyDir = $this->tester->getLegacyDir();
        $legacySessionName = $this->tester->getLegacySessionName();
        $defaultSessionName = $this->tester->getDefaultSessionName();
        $legacyScope = $this->tester->getLegacyScope();

        $moduleMapper = new ModuleNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope
        );

        $actionMapper = new ActionNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope
        );

        $converter = new RouteConverterHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $moduleMapper,
            $actionMapper
        );

        $routes = [
            '/login',
            '/logout',
            '/api',
            '/session-status'
        ];

        /** @var Router $router */
        $router = $this->make(
            Router::class,
            [
                'matchRequest' => static function (Request $request) use ($routes) {

                    if ($request->getPathInfo() === '/') {
                        return [];
                    }

                    foreach ($routes as $route) {
                        if (strpos($request->getPathInfo(), $route) === 0) {
                            return [];
                        }
                    }
                    throw new ResourceNotFoundException('path not found');
                },
            ]
        );

        $this->handler = new LegacyNonViewActionRedirectHandler($converter, $router, '/legacy');
    }

    /**
     * Test request match with suite 8 api request
     */
    public function testMatchCheckWithSuite8Request(): void
    {
        $queryParams = [
        ];

        $serverParams = [
            'REDIRECT_BASE' => '/suiteinstance',
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_REFERER' => 'http://localhost/suiteinstance/public/docs/graphql-playground/index.html',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/api/graphql',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/suiteinstance/api/graphql',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php',
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertFalse($valid);
    }

    /**
     * Test request match with suite 8 login request
     */
    public function testMatchCheckWithSuite8LoginRequest(): void
    {
        $queryParams = [
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/login',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertFalse($valid);
    }

    /**
     * Test request match with legacy view request
     */
    public function testMatchCheckWithLegacyViewRequest(): void
    {
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'ListView',
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'module=Accounts&action=index',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/index.php?module=Contacts&action=ListView',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertFalse($valid);
    }

    /**
     * Test request match with legacy entry point request
     */
    public function testMatchCheckWithLegacyEntryPointRequest(): void
    {
        $queryParams = [
            'entryPoint' => 'generatePdf'
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'entryPoint=generatePdf',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/index.php?entryPoint=generatePdf',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertTrue($valid);
    }

    /**
     * Test request match with legacy subpath request
     */
    public function testMatchCheckWithSubPathRequest(): void
    {
        $queryParams = [
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/something',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertTrue($valid);
    }

    /**
     * Test request match with legacy save request
     */
    public function testMatchCheckWithLegacySaveRequest(): void
    {
        $requestParameters = [
            'module' => 'Accounts',
            'record' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'isDuplicate' => 'false',
            'action' => 'Save',
            'return_module' => 'Accounts',
            'return_action' => 'DetailView',
            'return_id' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'module_tab' => '',
            'contact_role' => '',
            'relate_to' => 'Accounts',
            'relate_id' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'offset' => '1',
            'name' => 'V8 Api test Account',
            'phone_office' => '',
            'website' => 'http://',
            'phone_fax' => '',
            'Accounts_email_widget_id' => '0',
            'emailAddressWidget' => '1',
            'Accounts0emailAddress0' => '',
            'Accounts0emailAddressId0' => '',
            'Accounts0emailAddressVerifiedFlag' => 'true',
            'Accounts0emailAddressVerifiedEmailValue' => 'true',
            'Accounts0emailAddressPrimaryFlag' => 'Accounts0emailAddress0',
            'useEmailWidget' => 'true',
            'billing_address_street' => '',
            'billing_address_city' => '',
            'billing_address_state' => '',
            'billing_address_postalcode' => '',
            'billing_address_country' => '',
            'shipping_address_street' => '',
            'shipping_address_city' => '',
            'shipping_address_state' => '',
            'shipping_address_postalcode' => '',
            'shipping_address_country' => '',
            'description' => '',
            'assigned_user_name' => '',
            'assigned_user_id' => '',
            'account_type' => '',
            'industry' => '',
            'annual_revenue' => '',
            'employees' => '',
            'parent_name' => '',
            'parent_id' => '',
            'campaign_name' => ''
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'POST',
            'QUERY_STRING' => '',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/index.php',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request([], $requestParameters, [], [], [], $serverParams);

        $valid = $this->handler->isMatch($request);

        static::assertTrue($valid);
    }

    /**
     * Test path conversion with legacy entry point request
     */
    public function testPathConversionWithLegacyEntryPointRequest(): void
    {
        $resultingRoute = '/suiteinstance/legacy/index.php?entryPoint=generatePdf';

        $queryParams = [
            'entryPoint' => 'generatePdf'
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'entryPoint=generatePdf',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/index.php?entryPoint=generatePdf',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams, [], [], [], [], $serverParams);

        $route = $this->handler->convert($request);

        static::assertEquals($resultingRoute, $route);
    }

    /**
     * Test path conversion with legacy save request
     */
    public function testPathConversionWithLegacySaveRequest(): void
    {
        $resultingRoute = '/suiteinstance/legacy/index.php';

        $requestParameters = [
            'module' => 'Accounts',
            'record' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'isDuplicate' => 'false',
            'action' => 'Save',
            'return_module' => 'Accounts',
            'return_action' => 'DetailView',
            'return_id' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'module_tab' => '',
            'contact_role' => '',
            'relate_to' => 'Accounts',
            'relate_id' => '134f0d4a-3cb8-bf3b-e228-5eb42212b284',
            'offset' => '1',
            'name' => 'V8 Api test Account',
            'phone_office' => '',
            'website' => 'http://',
            'phone_fax' => '',
            'Accounts_email_widget_id' => '0',
            'emailAddressWidget' => '1',
            'Accounts0emailAddress0' => '',
            'Accounts0emailAddressId0' => '',
            'Accounts0emailAddressVerifiedFlag' => 'true',
            'Accounts0emailAddressVerifiedEmailValue' => 'true',
            'Accounts0emailAddressPrimaryFlag' => 'Accounts0emailAddress0',
            'useEmailWidget' => 'true',
            'billing_address_street' => '',
            'billing_address_city' => '',
            'billing_address_state' => '',
            'billing_address_postalcode' => '',
            'billing_address_country' => '',
            'shipping_address_street' => '',
            'shipping_address_city' => '',
            'shipping_address_state' => '',
            'shipping_address_postalcode' => '',
            'shipping_address_country' => '',
            'description' => '',
            'assigned_user_name' => '',
            'assigned_user_id' => '',
            'account_type' => '',
            'industry' => '',
            'annual_revenue' => '',
            'employees' => '',
            'parent_name' => '',
            'parent_id' => '',
            'campaign_name' => ''
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => '',
            'REQUEST_METHOD' => 'POST',
            'QUERY_STRING' => '',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/index.php',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request([], $requestParameters, [], [], [], $serverParams);

        $route = $this->handler->convert($request);

        static::assertEquals($resultingRoute, $route);
    }
}
