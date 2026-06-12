@extends('layouts.public')

@section('title', 'Terms of Service')
@section('meta_description', 'Terms governing use of '.platform_name().'.')

@section('content')
<section class="legal-hero">
    <div class="container-xl">
        <div class="public-kicker">Legal information</div>
        <h1>Terms of Service</h1>
        <p>The rules that keep account access, subscriptions, and business responsibilities clear.</p>
        <div class="legal-updated"><i data-lucide="calendar-days"></i> Last updated June 12, 2026</div>
    </div>
</section>

<section class="legal-section">
    <div class="container-xl legal-layout">
        @include('legal.partials.navigation')
        <article class="legal-document">
            <section><h2>Service</h2><p>{{ platform_name() }} provides hosted inventory, billing, account, reporting, and subscription features for businesses. You are responsible for the accuracy of data entered by your business and its staff.</p></section>
            <section><h2>Accounts and access</h2><p>The business owner controls staff access and must keep account credentials secure. You must not use the service for unlawful, fraudulent, or abusive activity.</p></section>
            <section><h2>Subscriptions and payments</h2><p>Paid access begins after full payment is confirmed. Prices, billing periods, included features, and usage limits are shown before purchase. Access may be paused when a trial, free period, or paid subscription ends.</p></section>
            <section><h2>Business records</h2><p>You remain responsible for reviewing invoices, taxes, stock quantities, balances, reports, and backups required for your business. {{ platform_name() }} does not provide legal, tax, or accounting advice.</p></section>
            <section><h2>Availability and changes</h2><p>We may maintain, improve, or change the service. We will make reasonable efforts to protect availability and business data but cannot promise uninterrupted operation.</p></section>
            <section><h2>Termination</h2><p>Accounts may be suspended for non-payment, security threats, misuse, or violation of these terms. Contact platform support before cancellation to discuss data export and account closure.</p></section>
            <section><h2>Contact</h2><p>Contact {{ platform_settings()->support_email ?: 'platform support' }} for questions about these terms.</p></section>
        </article>
    </div>
</section>
@endsection
