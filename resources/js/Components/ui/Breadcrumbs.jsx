import Icon from '@/Components/Icon';
import { Link } from '@inertiajs/react';

/**
 * items: [{ label, href? }] — el último elemento se muestra como página actual (sin link).
 */
export default function Breadcrumbs({ items = [] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Breadcrumb" className="mb-2">
            <ol className="flex flex-wrap items-center gap-1 text-sm text-slate-500 dark:text-slate-400">
                {items.map((item, indice) => {
                    const esUltimo = indice === items.length - 1;

                    return (
                        <li key={`${item.label}-${indice}`} className="flex items-center gap-1">
                            {indice > 0 && <Icon name="chevronRight" className="h-3.5 w-3.5 text-slate-300 dark:text-slate-600" />}
                            {item.href && !esUltimo ? (
                                <Link href={item.href} className="transition-colors hover:text-slate-700 dark:hover:text-slate-200">
                                    {item.label}
                                </Link>
                            ) : (
                                <span className={esUltimo ? 'font-medium text-slate-700 dark:text-slate-300' : ''}>{item.label}</span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
