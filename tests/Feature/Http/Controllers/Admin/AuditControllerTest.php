<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('AuditController', function () {
    describe('index', function () {
        it('renders audit page for admin', function () {
            $response = $this->actingAs($this->admin)->get(route('admin.audit.index'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('Admin/Audit'));
        });

        it('denies access to non-admin', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->get(route('admin.audit.index'))->assertForbidden();
        });

        it('returns paginated logs', function () {
            ActivityLog::create(['event' => 'login_success', 'ip_address' => '127.0.0.1']);
            ActivityLog::create(['event' => 'logout', 'ip_address' => '127.0.0.1']);

            $response = $this->actingAs($this->admin)->get(route('admin.audit.index'));

            $response->assertInertia(fn ($page) => $page
                ->has('logs.data')
                ->has('logs.current_page')
                ->has('logs.total')
            );
        });

        it('filters by event type', function () {
            ActivityLog::create(['event' => 'login_success', 'ip_address' => '127.0.0.1']);
            ActivityLog::create(['event' => 'logout', 'ip_address' => '127.0.0.1']);

            $response = $this->actingAs($this->admin)->get(route('admin.audit.index', ['event' => 'login_success']));

            $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
        });

        it('filters by target username search', function () {
            ActivityLog::create(['event' => 'user_created', 'target_username' => 'joao', 'ip_address' => '127.0.0.1']);
            ActivityLog::create(['event' => 'user_created', 'target_username' => 'maria', 'ip_address' => '127.0.0.1']);

            $response = $this->actingAs($this->admin)->get(route('admin.audit.index', ['search' => 'joao']));

            $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
        });

        it('passes filters back to the view', function () {
            $response = $this->actingAs($this->admin)->get(route('admin.audit.index', [
                'event'  => 'logout',
                'search' => 'user',
            ]));

            $response->assertInertia(fn ($page) => $page
                ->where('filters.event', 'logout')
                ->where('filters.search', 'user')
            );
        });

        it('orders logs by most recent first', function () {
            $this->travelTo(now()->subMinutes(5));
            ActivityLog::create(['event' => 'login_success', 'ip_address' => '127.0.0.1']);
            $this->travelBack();
            ActivityLog::create(['event' => 'logout', 'ip_address' => '127.0.0.1']);

            $response = $this->actingAs($this->admin)->get(route('admin.audit.index'));

            $logs = $response->original->getData()['page']['props']['logs']['data'];
            expect($logs[0]['event'])->toBe('logout');
            expect($logs[1]['event'])->toBe('login_success');
        });
    });
});
