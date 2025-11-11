<?php

use CleaniqueCoders\LaravelOrganization\Facades\LaravelOrganization as LaravelOrganizationFacade;
use CleaniqueCoders\LaravelOrganization\LaravelOrganization;

describe('LaravelOrganization Facade', function () {
    it('resolves to LaravelOrganization class', function () {
        $accessor = LaravelOrganizationFacade::getFacadeRoot();

        expect($accessor)->toBeInstanceOf(LaravelOrganization::class);
    });

    it('has correct facade accessor', function () {
        $reflection = new ReflectionClass(LaravelOrganizationFacade::class);
        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        $accessor = $method->invoke(null);

        expect($accessor)->toBe(LaravelOrganization::class);
    });
});
