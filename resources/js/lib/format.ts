const formatters = {
    number: new Intl.NumberFormat('ru-RU'),
    compact: new Intl.NumberFormat('ru-RU', { notation: 'compact', maximumFractionDigits: 1 }),
    dateTime: new Intl.DateTimeFormat('ru-RU', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }),
    dayShort: new Intl.DateTimeFormat('ru-RU', { day: '2-digit', month: '2-digit' }),
    money: new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }),
};

export function formatNumber(value: number): string {
    return formatters.number.format(value);
}

export function formatCompact(value: number): string {
    return formatters.compact.format(value);
}

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '—' : formatters.dateTime.format(date);
}

export function formatDayShort(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : formatters.dayShort.format(date);
}

export function formatBytes(bytes: number): string {
    if (bytes <= 0) {
        return '0 Б';
    }

    const units = ['Б', 'КБ', 'МБ', 'ГБ'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const size = bytes / 1024 ** exponent;

    return `${size.toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

export function formatLatency(ms: number | null): string {
    if (ms === null) {
        return '—';
    }

    return ms >= 1000 ? `${(ms / 1000).toFixed(2)} с` : `${ms} мс`;
}

export function relativeTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000);
    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['second', 60],
        ['minute', 60],
        ['hour', 24],
        ['day', 30],
        ['month', 12],
        ['year', Number.POSITIVE_INFINITY],
    ];

    const formatter = new Intl.RelativeTimeFormat('ru-RU', { numeric: 'auto' });
    let value2 = diffSeconds;

    for (const [unit, limit] of units) {
        if (Math.abs(value2) < limit) {
            return formatter.format(Math.round(value2), unit);
        }
        value2 /= limit;
    }

    return formatters.dateTime.format(date);
}
