import PrimaryButton from '@/Components/PrimaryButton';
import { Link } from '@inertiajs/react';

export default function EmptyState({ title, description = '', actionLabel, actionHref = null, onAction = null }) {
    return (
        <div className="py-8">
            <div className="mx-auto max-w-xl text-center">
                <div className="text-base font-semibold text-slate-900">{title}</div>
                {!!description && <div className="mt-1 text-sm text-slate-600">{description}</div>}

                {!!actionLabel && (
                    <div className="mt-4 flex items-center justify-center">
                        {actionHref ? (
                            <Link href={actionHref}>
                                <PrimaryButton type="button">{actionLabel}</PrimaryButton>
                            </Link>
                        ) : (
                            <PrimaryButton type="button" onClick={onAction}>
                                {actionLabel}
                            </PrimaryButton>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
