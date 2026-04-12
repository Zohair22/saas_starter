<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SaaS Starter') }} | Convert Visitors Into Paying SaaS Customers</title>
    <meta name="description" content="{{ config('app.name', 'SaaS Starter') }} helps founders and teams launch trusted multi-tenant SaaS with billing, permissions, proof-ready logs, and real-time collaboration.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-serif:400" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --paper: #fffef7;
            --line: #d6d3d1;
            --brand: #f59e0b;
            --brand-dark: #b45309;
            --mint: #14b8a6;
            --sky: #38bdf8;
        }

        body {
            margin: 0;
            color: var(--ink);
            font-family: 'Space Grotesk', sans-serif;
            background:
                radial-gradient(circle at 90% 10%, #e0f2fe 0%, rgba(224, 242, 254, 0) 38%),
                radial-gradient(circle at 5% 20%, #fef3c7 0%, rgba(254, 243, 199, 0) 42%),
                linear-gradient(160deg, #fffbeb 0%, #f8fafc 55%, #f0fdfa 100%);
        }

        .heading-serif {
            font-family: 'Instrument Serif', serif;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 620ms ease-out both;
        }

        .delay-1 { animation-delay: 80ms; }
        .delay-2 { animation-delay: 140ms; }
        .delay-3 { animation-delay: 200ms; }

        @media (prefers-reduced-motion: reduce) {
            .fade-up,
            .delay-1,
            .delay-2,
            .delay-3 {
                animation: none;
            }
        }
    </style>
</head>
@php
        $heroVariants = [
            'a' => [
                'headline' => 'Build trust fast. Convert faster. Launch your SaaS in days.',
                'subheadline' => config('app.name', 'SaaS Starter').' is for founders, agencies, and product teams who need multi-tenant infrastructure, billing, and permissions handled from day one so they can focus on product and growth.',
                'primaryCta' => 'Start free and build your first workspace',
                'secondaryCta' => 'View product demo',
            ],
            'b' => [
                'headline' => 'From idea to paying customers without rebuilding your stack.',
                'subheadline' => config('app.name', 'SaaS Starter').' gives B2B SaaS teams tenant isolation, subscriptions, role controls, and real-time operations in one production-ready foundation.',
                'primaryCta' => 'Create account and launch faster',
                'secondaryCta' => 'Explore the live product',
            ],
            'c' => [
                'headline' => 'Launch a SaaS your customers trust on day one.',
                'subheadline' => config('app.name', 'SaaS Starter').' helps teams ship secure workspaces, onboard users quickly, and turn adoption into recurring revenue with less engineering friction.',
                'primaryCta' => 'Start free workspace now',
                'secondaryCta' => 'See how it works',
            ],
        ];

        $heroVariantKey = strtolower((string) request()->query('ab', 'a'));
        if (! array_key_exists($heroVariantKey, $heroVariants)) {
            $heroVariantKey = 'a';
        }
        $heroCopy = $heroVariants[$heroVariantKey];
@endphp
<body class="antialiased" data-page="welcome" data-ab-variant="{{ $heroVariantKey }}">
    <main class="mx-auto w-full max-w-7xl px-5 py-6 sm:px-8 lg:px-10">
        <header class="sticky top-4 z-30 rounded-2xl border border-stone-200/80 bg-white/85 px-4 py-3 shadow-sm backdrop-blur sm:px-5">
            <div class="flex items-center justify-between gap-4">
                <a href="#" class="flex items-center gap-2 text-sm font-semibold tracking-[0.15em] text-slate-800 uppercase">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    {{ config('app.name', 'SaaS Starter') }}
                </a>

                <nav class="hidden items-center gap-5 text-sm font-medium text-slate-600 md:flex">
                    <a href="#features" class="hover:text-slate-900">Features</a>
                    <a href="#how-it-works" class="hover:text-slate-900">How It Works</a>
                    <a href="#pricing" class="hover:text-slate-900">Pricing</a>
                    <a href="#faq" class="hover:text-slate-900">FAQ</a>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" data-cta-id="nav-login" data-ab-variant="{{ $heroVariantKey }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Log in</a>
                    <a href="{{ route('register') }}" data-cta-id="nav-register" data-ab-variant="{{ $heroVariantKey }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Start free</a>
                </div>
            </div>
        </header>

        <section class="relative mt-10 overflow-hidden rounded-3xl border border-stone-200 bg-white/90 px-6 py-10 shadow-sm sm:px-10 sm:py-14">
            <div class="absolute -right-24 top-0 h-64 w-64 rounded-full bg-sky-200/45 blur-3xl"></div>
            <div class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-amber-200/45 blur-3xl"></div>

            <div class="relative grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <p class="fade-up inline-flex items-center rounded-full border border-amber-300 bg-amber-100/70 px-3 py-1 text-xs font-semibold tracking-wide text-amber-800 uppercase">
                        Multi-tenant SaaS platform
                    </p>
                    <h1 class="fade-up delay-1 mt-4 text-4xl leading-tight font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        {{ $heroCopy['headline'] }}
                    </h1>
                    <p class="fade-up delay-2 mt-5 max-w-2xl text-base leading-7 text-slate-700 sm:text-lg">
                        {{ $heroCopy['subheadline'] }}
                    </p>

                    <div class="fade-up delay-3 mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" data-cta-id="hero-primary" data-ab-variant="{{ $heroVariantKey }}" class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-400">{{ $heroCopy['primaryCta'] }}</a>
                        <a href="{{ route('login') }}" data-cta-id="hero-secondary" data-ab-variant="{{ $heroVariantKey }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50">{{ $heroCopy['secondaryCta'] }}</a>
                    </div>

                    <div class="mt-7 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                        <p><strong class="text-slate-900">What:</strong> Revenue-ready multi-tenant SaaS platform</p>
                        <p><strong class="text-slate-900">Who:</strong> Teams building B2B SaaS and client workspaces</p>
                        <p><strong class="text-slate-900">Trust:</strong> Laravel + Cashier + Horizon + Echo foundation</p>
                        <p><strong class="text-slate-900">Start:</strong> Register, create tenant, invite team, launch</p>
                    </div>
                </div>

                <aside class="fade-up delay-2 lg:col-span-5">
                    <div class="rounded-2xl border border-stone-200 bg-slate-900 p-5 text-slate-100 shadow-lg">
                        <p class="text-xs tracking-[0.16em] text-slate-300 uppercase">Conversion Snapshot</p>
                        <p class="mt-2 text-2xl font-bold">From first visit to first workspace in under 3 minutes</p>
                        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                                <p class="text-slate-300">Tenancy</p>
                                <p class="mt-1 font-semibold">Isolated by default</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                                <p class="text-slate-300">Billing</p>
                                <p class="mt-1 font-semibold">Stripe-ready</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                                <p class="text-slate-300">Realtime</p>
                                <p class="mt-1 font-semibold">Echo channels</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                                <p class="text-slate-300">Auditability</p>
                                <p class="mt-1 font-semibold">Activity + Audit logs</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="mt-10 rounded-2xl border border-stone-200 bg-white/85 p-6 shadow-sm" id="social-proof">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Social proof</p>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-2xl font-bold text-slate-900">120+</p>
                    <p class="mt-1 text-sm text-slate-600">Active workspaces</p>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-2xl font-bold text-slate-900">99.9%</p>
                    <p class="mt-1 text-sm text-slate-600">Webhook processing reliability</p>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-2xl font-bold text-slate-900">5 min</p>
                    <p class="mt-1 text-sm text-slate-600">Average onboarding time</p>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-2xl font-bold text-slate-900">24/7</p>
                    <p class="mt-1 text-sm text-slate-600">Operational visibility and alerts</p>
                </div>
            </div>
        </section>

        <section class="mt-10" id="features">
            <div class="mb-5">
                <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Features</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900 sm:text-4xl">Everything needed to ship, onboard, and monetize</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Tenant Isolation</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Strong tenant boundaries and scoped data access for every customer workspace.</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Role Permissions</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Owner/Admin/Member permissions with secure API enforcement and policy checks.</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Billing Engine</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Subscription workflows, webhook processing, and feature limits built-in.</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Activity Timeline</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Track critical user and system actions with searchable logs.</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Real-time Events</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Live updates for task collaboration and project events over WebSockets.</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Queue + Ops</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Background jobs and queue monitoring for reliable delivery at scale.</p>
                </article>
            </div>
        </section>

        <section class="mt-10 rounded-2xl border border-stone-200 bg-white/90 p-6 shadow-sm" id="how-it-works">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">How it works</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-xs font-semibold text-amber-700">Step 1</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Create your account</h3>
                    <p class="mt-2 text-sm text-slate-600">Register in seconds and open your first tenant workspace.</p>
                </article>
                <article class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-xs font-semibold text-amber-700">Step 2</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Invite your team</h3>
                    <p class="mt-2 text-sm text-slate-600">Assign roles and permissions, then organize projects and tasks.</p>
                </article>
                <article class="rounded-xl border border-stone-200 bg-white p-4">
                    <p class="text-xs font-semibold text-amber-700">Step 3</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Activate billing</h3>
                    <p class="mt-2 text-sm text-slate-600">Choose a plan, enforce limits, and turn adoption into predictable recurring revenue.</p>
                </article>
            </div>
        </section>

        <section class="mt-10" id="integrations">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Integrations</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm font-semibold text-slate-700">Laravel 13</div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm font-semibold text-slate-700">Stripe + Cashier</div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm font-semibold text-slate-700">Horizon Queues</div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm font-semibold text-slate-700">Echo Realtime</div>
            </div>
        </section>

        <section class="mt-10 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm" id="pricing">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Pricing teaser</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article class="rounded-xl border border-stone-200 bg-amber-50 p-4">
                    <h3 class="text-lg font-semibold text-slate-900">Starter</h3>
                    <p class="mt-1 text-sm text-slate-600">For first launch and validation</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900">$0</p>
                </article>
                <article class="rounded-xl border-2 border-slate-900 bg-white p-4">
                    <h3 class="text-lg font-semibold text-slate-900">Pro</h3>
                    <p class="mt-1 text-sm text-slate-600">For growing products and teams</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900">$49</p>
                </article>
                <article class="rounded-xl border border-stone-200 bg-teal-50 p-4">
                    <h3 class="text-lg font-semibold text-slate-900">Enterprise</h3>
                    <p class="mt-1 text-sm text-slate-600">For multi-team scale and compliance</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900">Custom</p>
                </article>
            </div>
            <a href="{{ route('register') }}" data-cta-id="pricing-primary" data-ab-variant="{{ $heroVariantKey }}" class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Compare plans and start free</a>
        </section>

        <section class="mt-10" id="testimonials">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Testimonials</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <blockquote class="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                    <p class="text-sm leading-6 text-slate-700">"We migrated from scattered tools to one workspace and reduced onboarding tickets in the first week."</p>
                    <footer class="mt-3 text-xs font-semibold text-slate-500">Operations Lead, Northline</footer>
                </blockquote>
                <blockquote class="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                    <p class="text-sm leading-6 text-slate-700">"Billing and tenant isolation were already solved, so we shipped value instead of plumbing."</p>
                    <footer class="mt-3 text-xs font-semibold text-slate-500">Founder, Studio Orbit</footer>
                </blockquote>
                <blockquote class="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                    <p class="text-sm leading-6 text-slate-700">"The activity and audit streams made enterprise customer reviews much easier."</p>
                    <footer class="mt-3 text-xs font-semibold text-slate-500">PM, Ember Cloud</footer>
                </blockquote>
            </div>
        </section>

        <section class="mt-10 rounded-2xl border border-stone-200 bg-white/90 p-6 shadow-sm" id="faq">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">FAQ</p>
            <div class="mt-4 space-y-3">
                <details class="rounded-xl border border-stone-200 bg-white p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-900">Can I use this for B2B SaaS?</summary>
                    <p class="mt-2 text-sm text-slate-600">Yes. It is built for B2B workflows with tenancy, role controls, and subscription logic.</p>
                </details>
                <details class="rounded-xl border border-stone-200 bg-white p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-900">Does it support teams and invitations?</summary>
                    <p class="mt-2 text-sm text-slate-600">Yes. Owner/Admin can invite members and manage role-based access from day one.</p>
                </details>
                <details class="rounded-xl border border-stone-200 bg-white p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-900">How fast can I start?</summary>
                    <p class="mt-2 text-sm text-slate-600">Create an account, open your workspace, and launch your first project in minutes.</p>
                </details>
            </div>
        </section>

        <section class="mt-10 rounded-3xl border border-slate-900 bg-slate-900 px-6 py-10 text-center text-white shadow-lg">
            <p class="text-xs tracking-[0.2em] text-slate-300 uppercase">Final call to action</p>
            <h2 class="mt-3 heading-serif text-4xl leading-tight">Turn visitors into customers with a product they trust from day one.</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-200">Start free, invite your team, and launch your first revenue-ready workspace without rebuilding core infrastructure.</p>
            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" data-cta-id="final-primary" data-ab-variant="{{ $heroVariantKey }}" class="rounded-xl bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-amber-300">Create free account</a>
                <a href="{{ route('login') }}" data-cta-id="final-secondary" data-ab-variant="{{ $heroVariantKey }}" class="rounded-xl border border-slate-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Sign in</a>
            </div>
        </section>

        <footer class="mt-10 border-t border-stone-300/70 py-6 text-sm text-slate-600">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <p>&copy; {{ now()->year }} {{ config('app.name', 'SaaS Starter') }}. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#faq" class="hover:text-slate-900">FAQ</a>
                    <a href="#pricing" class="hover:text-slate-900">Pricing</a>
                    <a href="{{ route('register') }}" class="hover:text-slate-900">Get Started</a>
                </div>
            </div>
        </footer>
    </main>

    <script>
        (function () {
            const endpoint = '{{ route('landing.track') }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!endpoint || !csrfToken) {
                return;
            }

            document.addEventListener('click', function (event) {
                const target = event.target.closest('a[data-cta-id]');

                if (!target) {
                    return;
                }

                const payload = {
                    _token: csrfToken,
                    page: document.body.dataset.page || 'welcome',
                    cta_id: target.dataset.ctaId,
                    ab_variant: target.dataset.abVariant || document.body.dataset.abVariant || null,
                    path: window.location.pathname + window.location.search,
                    referrer: document.referrer || null,
                };

                if (navigator.sendBeacon) {
                    const formData = new FormData();

                    Object.entries(payload).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            formData.append(key, value);
                        }
                    });

                    navigator.sendBeacon(endpoint, formData);
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    keepalive: true,
                    body: JSON.stringify(payload),
                }).catch(function () {
                    // Silently ignore tracking failures.
                });
            }, { capture: true });
        })();
    </script>
</body>
</html>
