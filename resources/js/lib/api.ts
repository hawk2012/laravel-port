import axios from 'axios';
import type { Generation, Paginated, Project } from '@/types';

const http = axios.create({
    baseURL: '/api/v1',
    headers: { Accept: 'application/json' },
    withCredentials: true,
});

http.interceptors.request.use((config) => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

    if (token) {
        config.headers.set('X-CSRF-TOKEN', token);
    }

    return config;
});

export interface ApiError {
    message: string;
    code?: string;
    errors?: Record<string, string[]>;
}

export function extractError(error: unknown): ApiError {
    if (axios.isAxiosError(error)) {
        const payload = error.response?.data as Partial<ApiError> | undefined;

        return {
            message: payload?.message ?? error.message,
            code: payload?.code,
            errors: payload?.errors,
        };
    }

    return { message: error instanceof Error ? error.message : 'Неизвестная ошибка' };
}

export const api = {
    async generations(params: Record<string, unknown> = {}): Promise<Paginated<Generation>> {
        const { data } = await http.get<Paginated<Generation>>('/generations', { params });

        return data;
    },

    async generation(uuid: string): Promise<Generation> {
        const { data } = await http.get<{ data: Generation }>(`/generations/${uuid}`);

        return data.data;
    },

    async createGeneration(projectId: number, payload: Record<string, unknown>): Promise<Generation> {
        const { data } = await http.post<{ data: Generation }>(`/projects/${projectId}/generations`, payload);

        return data.data;
    },

    async cancelGeneration(uuid: string): Promise<Generation> {
        const { data } = await http.post<{ data: Generation }>(`/generations/${uuid}/cancel`);

        return data.data;
    },

    async projects(params: Record<string, unknown> = {}): Promise<Paginated<Project>> {
        const { data } = await http.get<Paginated<Project>>('/projects', { params });

        return data;
    },

    async createProject(payload: Record<string, unknown>): Promise<Project> {
        const { data } = await http.post<{ data: Project }>('/projects', payload);

        return data.data;
    },

    async deleteProject(id: number): Promise<void> {
        await http.delete(`/projects/${id}`);
    },

    async usage(days = 30): Promise<Record<string, unknown>> {
        const { data } = await http.get<{ data: Record<string, unknown> }>('/usage', { params: { days } });

        return data.data;
    },
};

export { http };
