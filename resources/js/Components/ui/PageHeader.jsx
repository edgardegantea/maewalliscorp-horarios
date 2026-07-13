import Breadcrumbs from '@/Components/ui/Breadcrumbs';

export default function PageHeader({ title, description, actions, breadcrumbs }) {
    return (
        <div>
            {breadcrumbs && <Breadcrumbs items={breadcrumbs} />}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{title}</h1>
                    {description && (
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
                    )}
                </div>
                {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
            </div>
        </div>
    );
}
