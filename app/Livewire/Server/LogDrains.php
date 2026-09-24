<?php

namespace App\Livewire\Server;

use App\Actions\Server\StartLogDrain;
use App\Actions\Server\StopLogDrain;
use App\Models\Server;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LogDrains extends Component
{
    use AuthorizesRequests;

    public Server $server;

    #[Validate(['boolean'])]
    public bool $isLogDrainNewRelicEnabled = false;

    #[Validate(['boolean'])]
    public bool $isLogDrainCustomEnabled = false;

    #[Validate(['boolean'])]
    public bool $isLogDrainAxiomEnabled = false;

    #[Validate(['boolean'])]
    public bool $isLogDrainAwslogsEnabled = false;

    #[Validate(['string', 'nullable', 'regex:/^[a-zA-Z0-9_\-\.]+$/'])]
    public ?string $logDrainNewRelicLicenseKey = null;

    #[Validate(['url', 'nullable'])]
    public ?string $logDrainNewRelicBaseUri = null;

    #[Validate(['string', 'nullable', 'regex:/^[a-zA-Z0-9_\-\.]+$/'])]
    public ?string $logDrainAxiomDatasetName = null;

    #[Validate(['string', 'nullable', 'regex:/^[a-zA-Z0-9_\-\.]+$/'])]
    public ?string $logDrainAxiomApiKey = null;

    #[Validate(['string', 'nullable'])]
    public ?string $logDrainCustomConfig = null;

    #[Validate(['string', 'nullable'])]
    public ?string $logDrainCustomConfigParser = null;

    #[Validate(['string', 'nullable'])]
    public ?string $logDrainAwslogsOptions = null;

    public function mount(string $server_uuid)
    {
        try {
            $this->server = Server::ownedByCurrentTeam()->whereUuid($server_uuid)->firstOrFail();
            $this->syncData();
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    private function syncDataNewRelic(bool $toModel = false): void
    {
        if ($toModel) {
            $this->server->settings->is_logdrain_newrelic_enabled = $this->isLogDrainNewRelicEnabled;
            $this->server->settings->logdrain_newrelic_license_key = $this->logDrainNewRelicLicenseKey;
            $this->server->settings->logdrain_newrelic_base_uri = $this->logDrainNewRelicBaseUri;
        } else {
            $this->isLogDrainNewRelicEnabled = $this->server->settings->is_logdrain_newrelic_enabled;
            $this->logDrainNewRelicLicenseKey = auth()->user()->can('update', $this->server)
                ? $this->server->settings->logdrain_newrelic_license_key
                : null;
            $this->logDrainNewRelicBaseUri = $this->server->settings->logdrain_newrelic_base_uri;
        }
    }

    private function syncDataAxiom(bool $toModel = false): void
    {
        if ($toModel) {
            $this->server->settings->is_logdrain_axiom_enabled = $this->isLogDrainAxiomEnabled;
            $this->server->settings->logdrain_axiom_dataset_name = $this->logDrainAxiomDatasetName;
            $this->server->settings->logdrain_axiom_api_key = $this->logDrainAxiomApiKey;
        } else {
            $this->isLogDrainAxiomEnabled = $this->server->settings->is_logdrain_axiom_enabled;
            $this->logDrainAxiomDatasetName = $this->server->settings->logdrain_axiom_dataset_name;
            $this->logDrainAxiomApiKey = auth()->user()->can('update', $this->server)
                ? $this->server->settings->logdrain_axiom_api_key
                : null;
        }
    }

    private function syncDataCustom(bool $toModel = false): void
    {
        if ($toModel) {
            $this->server->settings->is_logdrain_custom_enabled = $this->isLogDrainCustomEnabled;
            $this->server->settings->logdrain_custom_config = $this->logDrainCustomConfig;
            $this->server->settings->logdrain_custom_config_parser = $this->logDrainCustomConfigParser;
        } else {
            $this->isLogDrainCustomEnabled = $this->server->settings->is_logdrain_custom_enabled;
            $this->logDrainCustomConfig = auth()->user()->can('update', $this->server)
                ? $this->server->settings->logdrain_custom_config
                : null;
            $this->logDrainCustomConfigParser = auth()->user()->can('update', $this->server)
                ? $this->server->settings->logdrain_custom_config_parser
                : null;
        }
    }

    private function syncDataAwslogs(bool $toModel = false): void
    {
        if ($toModel) {
            $this->server->settings->is_logdrain_awslogs_enabled = $this->isLogDrainAwslogsEnabled;
            $this->server->settings->logdrain_awslogs_options = $this->logDrainAwslogsOptions;
        } else {
            $this->isLogDrainAwslogsEnabled = $this->server->settings->is_logdrain_awslogs_enabled;
            $this->logDrainAwslogsOptions = auth()->user()->can('update', $this->server)
                ? $this->server->settings->logdrain_awslogs_options
                : null;
        }
    }

    private function syncData(bool $toModel = false, ?string $type = null): void
    {
        if ($toModel) {
            $this->customValidation();
            if ($type === 'newrelic') {
                $this->syncDataNewRelic($toModel);
            } elseif ($type === 'axiom') {
                $this->syncDataAxiom($toModel);
            } elseif ($type === 'custom') {
                $this->syncDataCustom($toModel);
            } elseif ($type === 'awslogs') {
                $this->syncDataAwslogs($toModel);
            } else {
                $this->syncDataNewRelic($toModel);
                $this->syncDataAxiom($toModel);
                $this->syncDataCustom($toModel);
                $this->syncDataAwslogs($toModel);
            }
            $this->auditLogDrain('updated');
            $this->server->settings->save();
        } else {
            if ($type === 'newrelic') {
                $this->syncDataNewRelic($toModel);
            } elseif ($type === 'axiom') {
                $this->syncDataAxiom($toModel);
            } elseif ($type === 'custom') {
                $this->syncDataCustom($toModel);
            } elseif ($type === 'awslogs') {
                $this->syncDataAwslogs($toModel);
            } else {
                $this->syncDataNewRelic($toModel);
                $this->syncDataAxiom($toModel);
                $this->syncDataCustom($toModel);
                $this->syncDataAwslogs($toModel);
            }
        }
    }

    public function customValidation()
    {
        if ($this->isLogDrainNewRelicEnabled) {
            try {
                $this->validate([
                    'logDrainNewRelicLicenseKey' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
                    'logDrainNewRelicBaseUri' => ['required', 'url'],
                ]);
            } catch (\Throwable $e) {
                $this->isLogDrainNewRelicEnabled = false;

                throw $e;
            }
        } elseif ($this->isLogDrainAxiomEnabled) {
            try {
                $this->validate([
                    'logDrainAxiomDatasetName' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
                    'logDrainAxiomApiKey' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
                ]);
            } catch (\Throwable $e) {
                $this->isLogDrainAxiomEnabled = false;

                throw $e;
            }
        } elseif ($this->isLogDrainCustomEnabled) {
            try {
                $this->validate([
                    'logDrainCustomConfig' => ['required'],
                    'logDrainCustomConfigParser' => ['string', 'nullable'],
                ]);
            } catch (\Throwable $e) {
                $this->isLogDrainCustomEnabled = false;

                throw $e;
            }
        } elseif ($this->isLogDrainAwslogsEnabled) {
            try {
                $this->validate([
                    'logDrainAwslogsOptions' => ['required', 'string'],
                ]);
                $this->validateAwslogsOptions();
            } catch (\Throwable $e) {
                $this->isLogDrainAwslogsEnabled = false;

                throw $e;
            }
        }
    }

    public function instantSave()
    {
        try {
            $this->authorize('update', $this->server);
            $this->syncData(true);
            $this->auditLogDrain('updated');
            if ($this->server->isLogDrainEnabled()) {
                StartLogDrain::run($this->server);
                $this->dispatch('success', 'Log drain service started.');
            } else {
                StopLogDrain::run($this->server);
                $this->dispatch('success', 'Log drain service stopped.');
            }
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function toggleLogDrain(string $type): void
    {
        $previousNewRelicEnabled = $this->server->settings->is_logdrain_newrelic_enabled;
        $previousAxiomEnabled = $this->server->settings->is_logdrain_axiom_enabled;
        $previousCustomEnabled = $this->server->settings->is_logdrain_custom_enabled;
        $previousAwslogsEnabled = $this->server->settings->is_logdrain_awslogs_enabled;

        try {
            $this->authorize('update', $this->server);
            $this->resetErrorBag();

            $enabledProperty = $this->enabledProperty($type);

            if ($this->{$enabledProperty}) {
                $this->{$enabledProperty} = false;
            } else {
                $this->validateLogDrainSettings($type);
                $this->isLogDrainNewRelicEnabled = $type === 'newrelic';
                $this->isLogDrainAxiomEnabled = $type === 'axiom';
                $this->isLogDrainCustomEnabled = $type === 'custom';
                $this->isLogDrainAwslogsEnabled = $type === 'awslogs';
            }

            $this->syncData(true);

            if ($this->server->isLogDrainEnabled()) {
                StartLogDrain::run($this->server);
                $this->dispatch('success', 'Log drain service started.');
            } else {
                StopLogDrain::run($this->server);
                $this->dispatch('success', 'Log drain service stopped.');
            }
        } catch (\Throwable $e) {
            // Restore the previously persisted enabled flags so the UI/DB never
            // claim a runtime state that the Start/StopLogDrain action failed to apply.
            $this->server->settings->is_logdrain_newrelic_enabled = $previousNewRelicEnabled;
            $this->server->settings->is_logdrain_axiom_enabled = $previousAxiomEnabled;
            $this->server->settings->is_logdrain_custom_enabled = $previousCustomEnabled;
            $this->server->settings->is_logdrain_awslogs_enabled = $previousAwslogsEnabled;
            $this->server->settings->save();
            $this->syncData();

            handleError($e, $this);
        }
    }

    public function submit()
    {
        try {
            $this->authorize('update', $this->server);
            $this->syncData(true);
            $this->dispatch('success', 'Settings saved.');
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function render()
    {
        return view('livewire.server.log-drains');
    }

    private function enabledProperty(string $type): string
    {
        return match ($type) {
            'newrelic' => 'isLogDrainNewRelicEnabled',
            'axiom' => 'isLogDrainAxiomEnabled',
            'custom' => 'isLogDrainCustomEnabled',
            'awslogs' => 'isLogDrainAwslogsEnabled',
            default => throw new \InvalidArgumentException('Unknown log drain type.'),
        };
    }

    private function auditLogDrain(string $action, ?string $type = null): void
    {
        auditLog("ui.server.log_drain.{$action}", [
            'team_id' => $this->server->team_id,
            'server_uuid' => $this->server->uuid,
            'server_name' => $this->server->name,
            'provider' => $type,
        ]);
    }

    private function validateLogDrainSettings(string $type): void
    {
        match ($type) {
            'newrelic' => $this->validate([
                'logDrainNewRelicLicenseKey' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
                'logDrainNewRelicBaseUri' => ['required', 'url'],
            ]),
            'axiom' => $this->validate([
                'logDrainAxiomDatasetName' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
                'logDrainAxiomApiKey' => ['required', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            ]),
            'custom' => $this->validate([
                'logDrainCustomConfig' => ['required'],
                'logDrainCustomConfigParser' => ['string', 'nullable'],
            ]),
            'awslogs' => $this->validate([
                'logDrainAwslogsOptions' => ['required', 'string'],
            ]),
            default => throw new \InvalidArgumentException('Unknown log drain type.'),
        };

        if ($type === 'awslogs') {
            $this->validateAwslogsOptions();
        }
    }

    private function validateAwslogsOptions(): void
    {
        $options = json_decode((string) $this->logDrainAwslogsOptions, true);
        if (! is_array($options)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'logDrainAwslogsOptions' => 'AWS logs options must be valid JSON.',
            ]);
        }

        $this->validate([
            'logDrainAwslogsOptions' => [
                function (string $attribute, mixed $value, \Closure $fail) use ($options): void {
                    if (! array_key_exists('awslogs-group', $options) || blank((string) $options['awslogs-group'])) {
                        $fail('The awslogs-group option is required.');
                    }
                    if (! array_key_exists('awslogs-region', $options) || blank((string) $options['awslogs-region'])) {
                        $fail('The awslogs-region option is required.');
                    }
                },
            ],
        ]);
    }
}
