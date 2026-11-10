import { route as ziggyRoute } from 'ziggy-js';

export type RouteParams = Record<string, string | number> | string | number | undefined;

/**
 * Конфиг Ziggy хранится в модуле, а не в window: так route() работает
 * одинаково в браузере и в Node-SSR, где window не существует.
 */
let config: unknown = null;

export function setRouteConfig(value: unknown): void {
    config = value;
}

export function route(name: string, params?: RouteParams, absolute = false): string {
    return ziggyRoute(name, params as never, absolute, config as never);
}