<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SaaS Starter') }} | Agency-Ready SaaS Foundation</title>
    <meta name="description" content="{{ config('app.name', 'SaaS Starter') }} helps agencies launch client-ready, multi-tenant SaaS products with billing, permissions, and operational tooling built in.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        <style>
            body {
                margin: 0;
                font-family: 'Space Grotesk', system-ui, sans-serif;
                color: #121212;
                background: radial-gradient(circle at 12% 18%, #f6d6aa 0%, #f7efe2 42%, #edf3f8 100%);
            }
        </style>
    @endif

    <style>
        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .anim-rise {
            opacity: 0;
            animation: riseIn 620ms ease-out forwards;
        }

        .anim-delay-1 {
            animation-delay: 80ms;
        }

        .anim-delay-2 {
            animation-delay: 160ms;
        }

        .anim-delay-3 {
            animation-delay: 240ms;
        }

        @media (prefers-reduced-motion: reduce) {
            .anim-rise,
            .anim-delay-1,
            .anim-delay-2,
            .anim-delay-3 {
                opacity: 1;
                animation: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_12%_18%,#f6d6aa_0%,#f7efe2_42%,#edf3f8_100%)] text-zinc-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-10 md:px-10 lg:px-12">
        <header class="anim-rise flex items-center justify-between rounded-2xl border border-zinc-200/80 bg-white/80 px-5 py-4 shadow-sm backdrop-blur md:px-6">
            <div class="flex items-center gap-3">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                <span class="text-sm font-semibold tracking-[0.16em] text-zinc-700 uppercase">{{ config('app.name', 'SaaS Starter') }}</span>
            </div>

            <nav class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-400 hover:bg-zinc-50">Log in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800">Create account</a>
            </nav>
        </header>

        <section class="relative mt-8 grid flex-1 items-center gap-8 lg:grid-cols-12">
            <div class="pointer-events-none absolute -top-6 right-8 h-28 w-28 rounded-full bg-amber-300/40 blur-2xl"></div>
            <div class="pointer-events-none absolute bottom-4 left-20 h-20 w-20 rounded-full bg-cyan-300/35 blur-2xl"></div>

            <article class="lg:col-span-7">
                <p class="anim-rise anim-delay-1 mb-4 inline-flex items-center rounded-full border border-amber-300 bg-amber-100/70 px-3 py-1 text-xs font-semibold tracking-wide text-amber-800 uppercase">
                    Agency-ready SaaS foundation
                </p>
                <h1 class="anim-rise anim-delay-1 text-4xl font-bold leading-tight tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                    {{ config('app.name', 'SaaS Starter') }} helps agencies deliver client-ready SaaS without rebuilding the core.
                </h1>
                <p class="anim-rise anim-delay-2 mt-5 max-w-2xl text-base leading-7 text-zinc-700 sm:text-lg">
                    Win projects faster with a battle-tested baseline for multi-tenant apps, subscriptions, permissions, and operational workflows.
                </p>

                <ul class="anim-rise anim-delay-2 mt-6 space-y-2 text-sm text-zinc-700 sm:text-base">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                        <span>Spin up separate client workspaces with strict tenant boundaries.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                        <span>Monetize each deployment with built-in subscription and billing flows.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                        <span>Maintain delivery quality with auditability, queues, and real-time updates.</span>
                    </li>
                </ul>

                <div class="anim-rise anim-delay-3 mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-zinc-900 shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-400">Start your agency stack</a>
                    <a href="/app" class="rounded-xl border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-400 hover:bg-zinc-50">View dashboard demo</a>
                </div>

                <p class="anim-rise anim-delay-3 mt-3 text-xs font-medium text-zinc-500 sm:text-sm">
                    No long rebuild phase. Create an account and onboard your first client workspace today.
                </p>

                <div class="mt-8 flex flex-wrap gap-2">
                    <span class="rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1 text-xs font-medium text-zinc-600">Tenant-aware APIs</span>
                    <span class="rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1 text-xs font-medium text-zinc-600">Stripe-ready billing</span>
                    <span class="rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1 text-xs font-medium text-zinc-600">Inertia + React UI</span>
                    <span class="rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1 text-xs font-medium text-zinc-600">Activity + audit logs</span>
                </div>
            </article>

            <aside class="anim-rise anim-delay-2 lg:col-span-5">
                <div class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-white p-6 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-200/55 blur-2xl"></div>
                    <div class="absolute -bottom-20 -left-10 h-44 w-44 rounded-full bg-cyan-200/45 blur-2xl"></div>

                    <div class="relative space-y-4">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                            <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Starter health</p>
                            <p class="mt-2 text-xl font-bold text-zinc-900">Agency delivery baseline</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-zinc-200 p-3">
                                <p class="text-xs text-zinc-500">Modules</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">7+</p>
                            </div>
                            <div class="rounded-xl border border-zinc-200 p-3">
                                <p class="text-xs text-zinc-500">Billing</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">Cashier</p>
                            </div>
                            <div class="rounded-xl border border-zinc-200 p-3">
                                <p class="text-xs text-zinc-500">Queues</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">Horizon</p>
                            </div>
                            <div class="rounded-xl border border-zinc-200 p-3">
                                <p class="text-xs text-zinc-500">Realtime</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">Echo</p>
                            </div>
                        </div>

                        <p class="text-sm leading-6 text-zinc-600">
                            Sign in to keep shipping, or create your account and launch your first client-ready workspace today.
                        </p>
                    </div>
                </div>
            </aside>
        </section>

        <section class="anim-rise anim-delay-3 mt-8 rounded-2xl border border-zinc-200/80 bg-white/80 p-5 shadow-sm backdrop-blur md:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold tracking-[0.14em] text-zinc-700 uppercase">Trusted By Delivery Teams</h2>
                <span class="text-xs text-zinc-500">Based on internal pilot feedback</span>
            </div>

            <div class="mb-4 rounded-xl border border-zinc-200 bg-white p-3">
                <p class="text-[11px] font-semibold tracking-[0.12em] text-zinc-500 uppercase">Powered By</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-zinc-900 text-[9px] font-bold text-white">L</span>
                        Laravel 13
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[9px] font-bold text-white">S</span>
                        Sanctum
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600 text-[9px] font-bold text-white">C</span>
                        Cashier
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-zinc-900">H</span>
                        Horizon
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-sky-600 text-[9px] font-bold text-white">E</span>
                        Echo
                    </span>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <blockquote class="rounded-xl border border-zinc-200 bg-white p-4">
                    <p class="text-sm leading-6 text-zinc-700">"We started with this baseline and shipped our first multi-tenant client portal in under two weeks."</p>
                    <footer class="mt-3 text-xs font-semibold text-zinc-500">Product Lead, Studio North</footer>
                </blockquote>

                <blockquote class="rounded-xl border border-zinc-200 bg-white p-4">
                    <p class="text-sm leading-6 text-zinc-700">"Billing and tenancy were already solved, so our team focused fully on client workflows."</p>
                    <footer class="mt-3 text-xs font-semibold text-zinc-500">Engineering Manager, Orbit Agency</footer>
                </blockquote>

                <blockquote class="rounded-xl border border-zinc-200 bg-white p-4">
                    <p class="text-sm leading-6 text-zinc-700">"Audit logs and role controls made enterprise approvals dramatically easier."</p>
                    <footer class="mt-3 text-xs font-semibold text-zinc-500">Solutions Architect, Ember Labs</footer>
                </blockquote>
            </div>
        </section>
    </main>
</body>
</html>
