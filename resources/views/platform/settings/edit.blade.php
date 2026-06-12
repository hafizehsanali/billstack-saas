@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Platform Settings</h1>
        <div class="text-muted">Manage registration access, support details, and subscription payment guidance.</div>
    </div>
    <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">Platform</a>
</div>

<form method="POST" action="{{ route('platform.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title mb-0">Platform Identity</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Platform Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="platform_name"
                           value="{{ old('platform_name', $settings->platform_name) }}"
                           class="form-control @error('platform_name') is-invalid @enderror"
                           required>
                    @error('platform_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Currency Code <span class="text-danger">*</span></label>
                    <input type="text"
                           name="currency_code"
                           maxlength="3"
                           value="{{ old('currency_code', $settings->currency_code) }}"
                           class="form-control text-uppercase @error('currency_code') is-invalid @enderror"
                           required>
                    @error('currency_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Support Email</label>
                    <input type="email"
                           name="support_email"
                           value="{{ old('support_email', $settings->support_email) }}"
                           class="form-control @error('support_email') is-invalid @enderror">
                    @error('support_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Support Phone</label>
                    <input type="text"
                           name="support_phone"
                           value="{{ old('support_phone', $settings->support_phone) }}"
                           class="form-control @error('support_phone') is-invalid @enderror">
                    @error('support_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title mb-0">Subscription Access</h2>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-check">
                    <input type="hidden" name="allow_registration" value="0">
                    <input type="checkbox"
                           name="allow_registration"
                           value="1"
                           class="form-check-input"
                           @checked(old('allow_registration', $settings->allow_registration))>
                    <span class="form-check-label">Allow new business registrations</span>
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label">Payment Instructions</label>
                <textarea name="payment_instructions"
                          rows="5"
                          class="form-control @error('payment_instructions') is-invalid @enderror">{{ old('payment_instructions', $settings->payment_instructions) }}</textarea>
                @error('payment_instructions')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @php
                $paymentChannels = old(
                    'payment_channels',
                    $settings->payment_channels ?: \App\Models\PlatformSetting::defaultPaymentChannels()
                );
            @endphp

            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label mb-0">Payment Channels</label>
                    <button type="button" id="add-payment-channel" class="btn btn-sm btn-outline-primary">
                        Add Channel
                    </button>
                </div>

                <div id="payment-channels" class="d-grid gap-3">
                    @foreach($paymentChannels as $index => $channel)
                        <div class="border rounded p-3 payment-channel">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">System Key</label>
                                    <input type="text"
                                           name="payment_channels[{{ $index }}][key]"
                                           value="{{ $channel['key'] ?? '' }}"
                                           class="form-control"
                                           placeholder="bank_transfer"
                                           required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Display Name</label>
                                    <input type="text"
                                           name="payment_channels[{{ $index }}][label]"
                                           value="{{ $channel['label'] ?? '' }}"
                                           class="form-control"
                                           placeholder="Bank Transfer"
                                           required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Account Title</label>
                                    <input type="text"
                                           name="payment_channels[{{ $index }}][account_title]"
                                           value="{{ $channel['account_title'] ?? '' }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Account / Wallet Number</label>
                                    <input type="text"
                                           name="payment_channels[{{ $index }}][account_number]"
                                           value="{{ $channel['account_number'] ?? '' }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Channel Instructions</label>
                                    <input type="text"
                                           name="payment_channels[{{ $index }}][instructions]"
                                           value="{{ $channel['instructions'] ?? '' }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-3 d-flex align-items-end justify-content-between">
                                    <label class="form-check mb-2">
                                        <input type="hidden"
                                               name="payment_channels[{{ $index }}][is_active]"
                                               value="0">
                                        <input type="checkbox"
                                               name="payment_channels[{{ $index }}][is_active]"
                                               value="1"
                                               class="form-check-input"
                                               @checked((bool) ($channel['is_active'] ?? false))>
                                        <span class="form-check-label">Active</span>
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-payment-channel">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('payment_channels')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<template id="payment-channel-template">
    <div class="border rounded p-3 payment-channel">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">System Key</label>
                <input type="text" data-name="key" class="form-control" placeholder="jazzcash" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Display Name</label>
                <input type="text" data-name="label" class="form-control" placeholder="JazzCash" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Account Title</label>
                <input type="text" data-name="account_title" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Account / Wallet Number</label>
                <input type="text" data-name="account_number" class="form-control">
            </div>
            <div class="col-md-9">
                <label class="form-label">Channel Instructions</label>
                <input type="text" data-name="instructions" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end justify-content-between">
                <label class="form-check mb-2">
                    <input type="hidden" data-name="is_active" value="0">
                    <input type="checkbox" data-name="is_active" value="1" class="form-check-input">
                    <span class="form-check-label">Active</span>
                </label>
                <button type="button" class="btn btn-sm btn-outline-danger remove-payment-channel">
                    Remove
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('payment-channels');
        const template = document.getElementById('payment-channel-template');

        const reindex = () => {
            container.querySelectorAll('.payment-channel').forEach((channel, index) => {
                channel.querySelectorAll('[data-name]').forEach(input => {
                    input.name = `payment_channels[${index}][${input.dataset.name}]`;
                });

                channel.querySelectorAll('[name^="payment_channels["]').forEach(input => {
                    input.name = input.name.replace(/payment_channels\[\d+]/, `payment_channels[${index}]`);
                });
            });
        };

        document.getElementById('add-payment-channel').addEventListener('click', () => {
            container.appendChild(template.content.cloneNode(true));
            reindex();
        });

        container.addEventListener('click', event => {
            const button = event.target.closest('.remove-payment-channel');

            if (! button || container.querySelectorAll('.payment-channel').length === 1) {
                return;
            }

            button.closest('.payment-channel').remove();
            reindex();
        });
    });
</script>
@endsection
