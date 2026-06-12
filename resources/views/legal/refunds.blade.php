@extends('layouts.public')

@section('title', 'Refund Policy')
@section('meta_description', 'Refund and cancellation policy for '.platform_name().' subscriptions.')

@section('content')
<section class="legal-hero">
    <div class="container-xl">
        <div class="public-kicker">Subscriptions and payments</div>
        <h1>Refund Policy</h1>
        <p>What to expect when reviewing payments, cancellations, and refund requests.</p>
        <div class="legal-updated"><i data-lucide="calendar-days"></i> Last updated June 12, 2026</div>
    </div>
</section>

<section class="legal-section">
    <div class="container-xl legal-layout">
        @include('legal.partials.navigation')
        <article class="legal-document">
            <section><h2>Trials and free access</h2><p>Use an available trial or free-access period to evaluate the service before purchasing a paid subscription.</p></section>
            <section><h2>Paid subscriptions</h2><p>Subscription payments are generally non-refundable after paid access is activated. Duplicate payments, incorrect charges, or payments that cannot be matched to an account will be reviewed individually.</p></section>
            <section><h2>Requesting a review</h2><p>Contact {{ platform_settings()->support_email ?: 'platform support' }} promptly with the business name, subscription invoice number, payment date, amount, method, and transaction reference.</p></section>
            <section><h2>Approved refunds</h2><p>Approved refunds are returned through an available payment method. Processing time depends on the bank, wallet, card provider, or other payment service involved.</p></section>
            <section><h2>Cancellation</h2><p>Cancellation prevents future renewal but does not automatically refund the current paid billing period. Access continues according to the subscription status shown in the account.</p></section>
        </article>
    </div>
</section>
@endsection
