<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CustomerAdminLayoutConsistencyTest extends TestCase
{
    /**
     * @return array<string>
     */
    private function customerViews(): array
    {
        return [
            'resources/views/customers/index.blade.php',
            'resources/views/customers/create.blade.php',
            'resources/views/customers/edit.blade.php',
            'resources/views/customers/show.blade.php',
        ];
    }

    public function test_all_customer_pages_use_one_shared_content_section(): void
    {
        foreach ($this->customerViews() as $view) {
            $source = file_get_contents(base_path($view));

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "@extends('layouts.admin')",
                $source,
                $view
            );
            $this->assertSame(
                1,
                substr_count($source, "@section('content')"),
                $view
            );
            $this->assertSame(
                1,
                substr_count($source, '@endsection'),
                $view
            );
            $this->assertSame(
                1,
                substr_count($source, "@push('styles')"),
                $view
            );
            $this->assertSame(
                1,
                substr_count($source, '@endpush'),
                $view
            );
            $this->assertStringContainsString(
                'class="customer-page"',
                $source,
                $view
            );
            $this->assertStringNotContainsString(
                '<!DOCTYPE html>',
                $source,
                $view
            );
            $this->assertStringNotContainsString(
                '<body>',
                $source,
                $view
            );

            $compiled = Blade::compileString($source);

            $this->assertIsString($compiled);
            $this->assertSame(
                1,
                substr_count($compiled, 'startSection(\'content\')'),
                $view
            );
            $this->assertSame(
                1,
                substr_count($compiled, 'stopSection()'),
                $view
            );
        }
    }

    public function test_customer_navigation_active_state_is_preserved(): void
    {
        $layout = file_get_contents(
            base_path('resources/views/layouts/admin.blade.php')
        );

        $this->assertIsString($layout);
        $this->assertStringContainsString(
            "route('customers.index')",
            $layout
        );
        $this->assertStringContainsString(
            "request()->routeIs('customers.*') ? 'active' : ''",
            $layout
        );
    }

    public function test_admin_layout_supports_page_stacks_and_smooth_entry(): void
    {
        $layout = file_get_contents(
            base_path('resources/views/layouts/admin.blade.php')
        );

        $this->assertIsString($layout);
        $this->assertStringContainsString("@stack('styles')", $layout);
        $this->assertStringContainsString("@stack('scripts')", $layout);
        $this->assertStringContainsString(
            'animation: admin-content-enter .22s ease-out both;',
            $layout
        );
        $this->assertStringContainsString(
            '@media (prefers-reduced-motion: reduce)',
            $layout
        );
    }

    public function test_customer_functional_routes_are_preserved(): void
    {
        $index = file_get_contents(
            base_path('resources/views/customers/index.blade.php')
        );
        $create = file_get_contents(
            base_path('resources/views/customers/create.blade.php')
        );
        $edit = file_get_contents(
            base_path('resources/views/customers/edit.blade.php')
        );
        $show = file_get_contents(
            base_path('resources/views/customers/show.blade.php')
        );

        $this->assertStringContainsString(
            "route('customers.import')",
            $index
        );
        $this->assertStringContainsString(
            "route('customers.bulk-status')",
            $index
        );
        $this->assertStringContainsString(
            "route('customers.store')",
            $create
        );
        $this->assertStringContainsString(
            "route('customers.update', \$customer->id)",
            $edit
        );
        $this->assertStringContainsString(
            "route('customers.toggle-active', \$customer)",
            $show
        );
        $this->assertStringContainsString(
            "route('customers.statement', \$customer)",
            $show
        );
    }
}
