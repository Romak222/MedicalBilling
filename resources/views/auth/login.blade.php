<x-layouts.app :title="config('app.name').' Login'">
    <div class="app-background flex min-h-screen items-center justify-center px-5 py-10">
        <main class="grid w-full max-w-5xl overflow-hidden rounded-lg border border-white/70 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="clinical-sidebar hidden p-8 text-white lg:flex lg:flex-col lg:justify-between">
                <div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-pharma-600 text-2xl font-black leading-none tracking-normal text-white shadow-lg shadow-pharma-600/20">
                        +
                    </div>
                    <p class="mt-6 text-[11px] font-semibold uppercase text-slate-400">{{ config('pharmacy.store_code') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal">{{ config('app.name') }}</h1>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-slate-300">Local access for store setup, catalogue control, and audit-aware operations.</p>
                </div>

                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Core</p>
                        <p class="mt-1 font-semibold text-white">Offline</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Access</p>
                        <p class="mt-1 font-semibold text-white">Roles</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-3">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Phase</p>
                        <p class="mt-1 font-semibold text-white">5</p>
                    </div>
                </div>
            </section>

            <section class="p-6 sm:p-8">
                <div>
                    <p class="section-kicker">{{ config('pharmacy.store_code') }}</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-normal text-ink-950">Sign in</h2>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                    @csrf

                    <label class="block">
                        <span class="text-sm font-semibold text-ink-700">Email</span>
                        <input
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            autofocus
                            class="mt-1 field-control"
                        >
                        @error('email') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-ink-700">Password</span>
                        <input
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            class="mt-1 field-control"
                        >
                        @error('password') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <button
                        type="submit"
                        class="btn-primary w-full"
                    >
                        Sign In
                    </button>
                </form>
            </section>
        </main>
    </div>
</x-layouts.app>
