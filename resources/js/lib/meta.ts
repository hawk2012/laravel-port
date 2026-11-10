import type { GenerationStatus, GenerationType } from '@/types';

interface StatusMeta {
    label: string;
    dot: string;
    text: string;
    chip: string;
}

export const STATUS_META: Record<GenerationStatus, StatusMeta> = {
    queued: {
        label: 'В очереди',
        dot: 'bg-warn-400',
        text: 'text-warn-400',
        chip: 'border-warn-400/30 bg-warn-400/10 text-warn-400',
    },
    processing: {
        label: 'Генерация',
        dot: 'bg-brand-400',
        text: 'text-brand-400',
        chip: 'border-brand-400/30 bg-brand-400/10 text-brand-400',
    },
    completed: {
        label: 'Готово',
        dot: 'bg-success-400',
        text: 'text-success-400',
        chip: 'border-success-400/30 bg-success-400/10 text-success-400',
    },
    failed: {
        label: 'Ошибка',
        dot: 'bg-danger-400',
        text: 'text-danger-400',
        chip: 'border-danger-400/30 bg-danger-400/10 text-danger-400',
    },
    cancelled: {
        label: 'Отменено',
        dot: 'bg-mist-400',
        text: 'text-mist-300',
        chip: 'border-white/10 bg-white/5 text-mist-300',
    },
};

export const TYPE_META: Record<GenerationType, { label: string; icon: string; credits: number; accent: string }> = {
    image: { label: 'Изображение', icon: 'image', credits: 5, accent: 'text-brand-400' },
    video: { label: 'Видео', icon: 'video', credits: 40, accent: 'text-danger-400' },
    audio: { label: 'Аудио', icon: 'audio', credits: 10, accent: 'text-accent-400' },
    text: { label: 'Текст', icon: 'text', credits: 1, accent: 'text-warn-400' },
};

export const TYPE_OPTIONS: GenerationType[] = ['image', 'video', 'audio', 'text'];

export const STATUS_OPTIONS: GenerationStatus[] = ['queued', 'processing', 'completed', 'failed', 'cancelled'];
