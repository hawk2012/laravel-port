export interface AuthUser {
    id: number;
    name: string;
    email: string;
    plan: 'free' | 'pro' | 'scale';
    plan_label: string;
    credits_balance: number;
    concurrent_limit: number;
}

export interface SharedProps extends Record<string, unknown> {
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    ziggy?: unknown;
}

export type GenerationType = 'image' | 'video' | 'audio' | 'text';
export type GenerationStatus = 'queued' | 'processing' | 'completed' | 'failed' | 'cancelled';
export type ProjectStatus = 'active' | 'archived';
export type AssetKind = GenerationType;

export interface Project {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: ProjectStatus;
    settings: Record<string, unknown> | null;
    generations_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Asset {
    id: number;
    uuid: string;
    kind: AssetKind;
    mime_type: string;
    size_bytes: number;
    width: number | null;
    height: number | null;
    duration_ms: number | null;
    meta: Record<string, unknown> | null;
    created_at: string;
    preview_url?: string | null;
    text?: string | null;
}

export interface Generation {
    uuid: string;
    type: GenerationType;
    status: GenerationStatus;
    status_label: string;
    prompt: string;
    negative_prompt: string | null;
    params: Record<string, unknown> | null;
    provider: string | null;
    model: string | null;
    credits_used: number;
    input_tokens: number;
    output_tokens: number;
    latency_ms: number | null;
    error_message: string | null;
    project?: Pick<Project, 'id' | 'name' | 'slug'> | null;
    assets: Asset[];
    queued_at: string | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number | null;
        to: number | null;
    };
}

export interface DashboardStats {
    status_counts: Record<string, number>;
    type_counts: Record<GenerationType, number>;
    daily: Array<{ date: string; total: number; failed: number }>;
    recent: Generation[];
    project_stats: {
        active: number;
        archived: number;
        total: number;
    };
    usage: {
        generations: number;
        completed: number;
        failed: number;
        credits_spent: number;
        input_tokens: number;
        output_tokens: number;
        avg_latency_ms: number;
        by_type: Record<string, number>;
    };
}

export type FlashLevel = 'success' | 'error';