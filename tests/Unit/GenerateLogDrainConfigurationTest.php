<?php

use App\Models\Server;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns fluentd logging configuration when awslogs is disabled', function () {
    $team = Team::factory()->create();
    $server = Server::factory()->create(['team_id' => $team->id]);

    $configuration = generate_log_drain_configuration($server->fresh(['settings']));

    expect($configuration['driver'])->toBe('fluentd')
        ->and($configuration['options'])->toHaveKey('fluentd-address');
});

it('returns awslogs logging configuration when awslogs is enabled with valid options', function () {
    $team = Team::factory()->create();
    $server = Server::factory()->create(['team_id' => $team->id]);
    $server->settings()->update([
        'is_logdrain_awslogs_enabled' => true,
        'logdrain_awslogs_options' => json_encode([
            'awslogs-group' => 'coolify-logs',
            'awslogs-region' => 'us-east-1',
            'awslogs-create-group' => true,
        ], JSON_THROW_ON_ERROR),
    ]);

    $configuration = generate_log_drain_configuration($server->fresh(['settings']));

    expect($configuration['driver'])->toBe('awslogs')
        ->and($configuration['options'])->toMatchArray([
            'awslogs-group' => 'coolify-logs',
            'awslogs-region' => 'us-east-1',
            'awslogs-create-group' => 'true',
        ]);
});
