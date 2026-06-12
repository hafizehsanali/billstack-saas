@extends('layouts.public')

@section('title', 'Privacy Policy')
@section('meta_description', 'How '.platform_name().' processes and protects account and business information.')

@section('content')
<section class="legal-hero">
    <div class="container-xl">
        <div class="public-kicker">Data and privacy</div>
        <h1>Privacy Policy</h1>
        <p>How account, subscription, and business information is used to operate the service.</p>
        <div class="legal-updated"><i data-lucide="calendar-days"></i> Last updated June 12, 2026</div>
    </div>
</section>

<section class="legal-section">
    <div class="container-xl legal-layout">
        @include('legal.partials.navigation')
        <article class="legal-document">
            <section><h2>Information we process</h2><p>We process account details, business settings, subscription records, and the inventory, customer, supplier, invoice, purchase, payment, and expense data entered by authorized users.</p></section>
            <section><h2>How information is used</h2><p>Information is used to provide the service, secure accounts, process subscriptions, deliver notifications, support customers, diagnose failures, and improve platform reliability.</p></section>
            <section><h2>Business responsibility</h2><p>Each business controls the personal information it enters about customers, suppliers, and staff. Business owners are responsible for having a lawful reason to collect and use that information.</p></section>
            <section><h2>Sharing and service providers</h2><p>Information may be processed by hosting, email, storage, monitoring, and payment providers needed to operate {{ platform_name() }}. We do not sell business data.</p></section>
            <section><h2>Security and retention</h2><p>We use access controls, tenant separation, encrypted connections, and operational safeguards. Records are retained while needed to provide the service, meet legal duties, resolve disputes, and maintain financial history.</p></section>
            <section><h2>Requests</h2><p>Contact {{ platform_settings()->support_email ?: 'platform support' }} to request access, correction, export, or deletion where applicable. Some financial or security records may need to be retained.</p></section>
        </article>
    </div>
</section>
@endsection
