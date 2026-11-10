<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GenerationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GenerationType::class)],
            'prompt' => ['required', 'string', 'min:3', 'max:'.config('ai.max_prompt_length', 2000)],
            'negative_prompt' => ['nullable', 'string', 'max:500'],
            'params' => ['nullable', 'array'],
            'params.size' => ['nullable', 'string', Rule::in(['1024x1024', '1024x576', '576x1024'])],
            'params.aspect_ratio' => ['nullable', 'string', Rule::in(['1:1', '16:9', '9:16'])],
            'params.temperature' => ['nullable', 'numeric', 'between:0,2'],
            'idempotency_key' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Укажите тип генерации.',
            'prompt.required' => 'Промпт обязателен.',
            'prompt.min' => 'Промпт слишком короткий — минимум 3 символа.',
        ];
    }
}
