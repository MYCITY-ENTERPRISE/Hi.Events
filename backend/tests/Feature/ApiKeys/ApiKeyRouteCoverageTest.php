<?php

namespace Tests\Feature\ApiKeys;

use HiEvents\DomainObjects\Enums\ApiKeyAbility;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * API keys are refused on every route by default. This test makes sure every authenticated route has been
 * classified explicitly: when a new route is added to the authenticated group, it fails until the route is
 * either given an ability in config/api-keys.php or listed as forbidden to API keys.
 */
class ApiKeyRouteCoverageTest extends TestCase
{
    private const AUTHENTICATED_MIDDLEWARE = 'auth:api,sanctum';

    public function test_every_authenticated_route_is_classified_for_api_keys(): void
    {
        $classified = array_merge(
            array_keys(config('api-keys.routes')),
            config('api-keys.open_routes'),
            config('api-keys.forbidden_routes'),
        );

        $unclassified = array_values(array_diff($this->authenticatedRoutes(), $classified));

        $this->assertSame([], $unclassified, 'Classify these routes in config/api-keys.php');
    }

    public function test_every_classified_route_exists_and_is_classified_once(): void
    {
        $classified = array_merge(
            array_keys(config('api-keys.routes')),
            config('api-keys.open_routes'),
            config('api-keys.forbidden_routes'),
        );

        $this->assertSame([], array_values(array_diff($classified, $this->authenticatedRoutes())), 'Unknown routes');
        $this->assertSame([], array_values(array_diff_assoc($classified, array_unique($classified))), 'Duplicates');
    }

    public function test_read_abilities_only_open_get_routes(): void
    {
        foreach (config('api-keys.routes') as $route => $ability) {
            $ability = ApiKeyAbility::from($ability);
            [$method] = explode(' ', $route, 2);

            if (! $ability->allowsWrites()) {
                $this->assertSame('GET', $method, "$route can't be opened by $ability->value");
            }
        }

        $this->assertSame(['GET api-key'], config('api-keys.open_routes'));
    }

    public function test_management_and_session_routes_are_forbidden(): void
    {
        $forbidden = config('api-keys.forbidden_routes');

        foreach ([
            'GET auth/logout',
            'POST auth/refresh',
            'GET accounts/{account_id}/api-keys',
            'POST accounts/{account_id}/api-keys',
            'DELETE accounts/{account_id}/api-keys/{api_key_id}',
            'GET users',
            'GET users/me',
            'POST organizers/{organizerId}/stripe/connect',
            'POST events/{event_id}/orders/export',
            'POST events/{event_id}/attendees/export',
        ] as $route) {
            $this->assertContains($route, $forbidden);
        }
    }

    /**
     * @return string[] "METHOD uri" of every route that accepts an API key
     */
    private function authenticatedRoutes(): array
    {
        $routes = [];

        /** @var Route $route */
        foreach (RouteFacade::getRoutes() as $route) {
            if (! in_array(self::AUTHENTICATED_MIDDLEWARE, $route->gatherMiddleware(), true)) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[] = $method.' '.$route->uri();
            }
        }

        sort($routes);

        return $routes;
    }
}
