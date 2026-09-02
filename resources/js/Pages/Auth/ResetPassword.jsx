import { Head, Link, useForm } from '@inertiajs/react';

const inputClass =
    'w-full rounded-xl border border-ink/15 bg-white/70 px-4 py-2.5 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-ink/40 focus:ring-2 focus:ring-periwinkle/30';

export default function ResetPassword({ email, token }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <div className="relative min-h-screen overflow-hidden bg-bone font-sans text-ink">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-blush/50 blur-[120px]" />
                <div className="absolute top-1/4 -right-32 h-[26rem] w-[26rem] rounded-full bg-periwinkle/40 blur-[120px]" />
                <div className="absolute bottom-0 left-1/3 h-[22rem] w-[22rem] rounded-full bg-citron/50 blur-[110px]" />
            </div>

            <Head title="Reset Password" />

            <div className="relative flex min-h-screen items-center justify-center px-4 py-12">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <Link href="/" className="inline-flex h-12 w-12 items-center justify-center">
                            <img src="/images/nomdal-favicon.png" alt="Nomdal" className="h-12 w-12 object-contain" />
                        </Link>
                        <h1 className="mt-4 font-display text-2xl font-black tracking-tight text-ink">Nomdal</h1>
                        <p className="mt-1 text-sm text-ink-dim">Set a new password</p>
                    </div>

                    <div className="rounded-3xl border border-ink/10 bg-white/60 p-8 shadow-sm backdrop-blur">
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <label htmlFor="email" className="mb-1 block text-sm font-semibold text-ink">
                                    Email address
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className={inputClass}
                                    placeholder="you@company.com"
                                    required
                                    autoFocus
                                />
                                {errors.email && <p className="mt-1 text-xs text-wine">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="password" className="mb-1 block text-sm font-semibold text-ink">
                                    New password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className={inputClass}
                                    placeholder="At least 8 characters"
                                    required
                                />
                                {errors.password && <p className="mt-1 text-xs text-wine">{errors.password}</p>}
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="mb-1 block text-sm font-semibold text-ink">
                                    Confirm password
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className={inputClass}
                                    placeholder="Repeat your password"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-full bg-ink px-4 py-3 text-sm font-bold text-bone transition hover:bg-forest disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Resetting...' : 'Reset password'}
                            </button>
                        </form>
                    </div>

                    <p className="mt-6 text-center text-sm text-ink-dim">
                        <Link href="/login" className="font-semibold text-forest hover:underline">
                            Back to sign in
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
