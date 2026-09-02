import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post('/email/verification-notification');
    };

    return (
        <div className="relative min-h-screen overflow-hidden bg-bone font-sans text-ink">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-blush/50 blur-[120px]" />
                <div className="absolute top-1/4 -right-32 h-[26rem] w-[26rem] rounded-full bg-periwinkle/40 blur-[120px]" />
                <div className="absolute bottom-0 left-1/3 h-[22rem] w-[22rem] rounded-full bg-citron/50 blur-[110px]" />
            </div>

            <Head title="Verify Email" />

            <div className="relative flex min-h-screen items-center justify-center px-4 py-12">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <Link href="/" className="inline-flex h-12 w-12 items-center justify-center">
                            <img src="/images/nomdal-favicon.png" alt="Nomdal" className="h-12 w-12 object-contain" />
                        </Link>
                        <h1 className="mt-4 font-display text-2xl font-black tracking-tight text-ink">Nomdal</h1>
                        <p className="mt-1 text-sm text-ink-dim">Verify your email address</p>
                    </div>

                    <div className="rounded-3xl border border-ink/10 bg-white/60 p-8 shadow-sm backdrop-blur">
                        <p className="text-sm leading-relaxed text-ink-dim">
                            Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you.
                        </p>

                        {status === 'verification-link-sent' && (
                            <p className="mt-4 rounded-xl border border-lime/40 bg-lime/10 px-4 py-3 text-sm text-forest">
                                A new verification link has been sent to your email address.
                            </p>
                        )}

                        <form onSubmit={submit} className="mt-6">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-full bg-ink px-4 py-3 text-sm font-bold text-bone transition hover:bg-forest disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Sending...' : 'Resend verification email'}
                            </button>
                        </form>

                        <div className="mt-4 text-center">
                            <Link href="/logout" method="post" as="button" className="text-sm font-semibold text-ink-dim hover:text-ink">
                                Log out
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
