
@extends('layouts.app')

@section('title', 'Returns & Refunds')

@section('content')
<main class="form-page returns-page">

    <div class="page-heading">
        <div>
            <span class="eyebrow muted">NOVACART CUSTOMER CARE</span>
            <h1>Returns &amp; Refunds</h1>
            <p>We're here to help make things right.</p>
        </div>
        <a class="primary" href="{{ route('orders') }}">View my orders</a>
    </div>

    <section class="returns-intro">
        <h2>Shopping should feel right.</h2>
        <p>
            Received a damaged product, the wrong item, or something that
            isn't right? Review our return policy and submit a request
            for an eligible order.
        </p>
        <a class="primary" href="{{ route('orders') }}">Start a return</a>
    </section>

    <section class="returns-section">
        <h2>Our return policy</h2>
        <p>Our current store policy asks customers to request a return
            within <strong>7 days of delivery</strong>.</p>

        <div class="returns-grid">
            <article class="returns-card">
                <h3>Return window</h3>
                <p>Submit your request within 7 days of receiving your order.</p>
            </article>

            <article class="returns-card">
                <h3>Product condition</h3>
                <p>Keep the product, packaging and accessories available
                    until support confirms the return instructions.</p>
            </article>

            <article class="returns-card">
                <h3>Return approval</h3>
                <p>Each request is reviewed before the next steps are confirmed.</p>
            </article>
        </div>
    </section>

    <section class="returns-section">
        <h2>How it works</h2>

        <div class="returns-steps">
            <article>
                <span class="returns-step-number">01</span>
                <div>
                    <h3>Find your order</h3>
                    <p>Open Your Orders and select the order you need help with.</p>
                </div>
            </article>

            <article>
                <span class="returns-step-number">02</span>
                <div>
                    <h3>Submit a request</h3>
                    <p>Choose the return option if available and explain the issue.</p>
                </div>
            </article>

            <article>
                <span class="returns-step-number">03</span>
                <div>
                    <h3>Wait for review</h3>
                    <p>Check your order or return status for updates and instructions.</p>
                </div>
            </article>

            <article>
                <span class="returns-step-number">04</span>
                <div>
                    <h3>Track your refund</h3>
                    <p>If a refund is approved, follow the status and instructions
                        provided for your order.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="returns-section">
        <h2>When can I request a return?</h2>

        <div class="returns-grid">
            <article class="returns-card">
                <h3>Damaged product</h3>
                <p>Contact us with your order details and a description of the damage.</p>
            </article>

            <article class="returns-card">
                <h3>Wrong product</h3>
                <p>Tell us what you received and what you expected to receive.</p>
            </article>

            <article class="returns-card">
                <h3>Other concerns</h3>
                <p>Explain the issue so our team can review your request.</p>
            </article>
        </div>

        <p class="small muted">
            Return eligibility depends on the order and the applicable store policy.
            A request does not guarantee approval.
        </p>
    </section>

    <section class="returns-section">
        <h2>Refund information</h2>
        <p>
            Refund eligibility and processing depend on the outcome of the
            return review. Any applicable refund method and processing
            instructions will be communicated when your request is handled.
        </p>
        <p>
            If your refund appears delayed, contact customer support with
            your order details.
        </p>
    </section>

    <section class="returns-help">
        <h2>Still need help?</h2>
        <p>Our support page is available for questions about orders,
            returns and refunds.</p>

        <div class="returns-help-actions">
            <a class="primary" href="{{ route('help') }}">Contact support</a>
            <a class="outline" href="{{ route('orders') }}">View my orders</a>
        </div>
    </section>

</main>
@endsection

@section('scripts')
@parent
<style>
    .returns-page {
        max-width: 1120px;
        margin: 0 auto;
        padding: 36px 24px 64px;
    }

    .returns-intro {
        margin: 28px 0;
        padding: 32px;
        border-radius: 16px;
        background: #173c42;
        color: #fff;
    }

    .returns-intro h2 {
        margin-top: 0;
        color: #fff;
    }

    .returns-intro p {
        max-width: 650px;
        color: #d9e5e5;
        line-height: 1.8;
    }

    .returns-intro .primary {
        display: inline-block;
        margin-top: 12px;
        background: #d5ef83;
        color: #173c42;
        text-decoration: none;
    }

    .returns-section {
        margin: 36px 0;
    }

    .returns-section > h2,
    .returns-help h2 {
        margin-bottom: 12px;
    }

    .returns-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .returns-card {
        padding: 22px;
        border: 1px solid #dce3e3;
        border-radius: 12px;
        background: #fff;
    }

    .returns-card h3 {
        margin: 0 0 10px;
    }

    .returns-card p,
    .returns-steps p {
        margin-bottom: 0;
        color: #65737b;
        line-height: 1.7;
    }

    .returns-steps {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
        margin-top: 20px;
    }

    .returns-steps article {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .returns-step-number {
        display: flex;
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #edf3e3;
        color: #173c42;
        font-weight: 700;
    }

    .returns-steps h3 {
        margin: 4px 0 6px;
    }

    .returns-help {
        margin-top: 40px;
        padding: 28px;
        border-radius: 14px;
        background: #edf3e8;
    }

    .returns-help-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 18px;
    }

    .returns-help-actions a {
        text-decoration: none;
    }

    @media (max-width: 700px) {
        .returns-page {
            padding: 24px 16px 40px;
        }

        .returns-intro {
            padding: 24px;
        }

        .returns-grid,
        .returns-steps {
            grid-template-columns: 1fr;
        }

        .page-heading {
            gap: 16px;
            flex-wrap: wrap;
        }
    }
</style>
@endsection
