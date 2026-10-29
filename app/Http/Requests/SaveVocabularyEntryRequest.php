<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveVocabularyEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tiền xử lý dữ liệu trước khi validate:
     * Nếu gửi nhiều từ (entries), loại bỏ các ô nhập hoàn toàn rỗng.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('entries') && is_array($this->entries)) {
            $filtered = array_filter($this->entries, function ($entry) {
                if (! is_array($entry)) {
                    return false;
                }
                $term = trim($entry['term'] ?? '');
                $meaning = trim($entry['meaning'] ?? '');
                $pronunciation = trim($entry['pronunciation'] ?? '');
                $pos = trim($entry['part_of_speech'] ?? '');
                $example = trim($entry['example_sentence'] ?? '');
                $trans = trim($entry['example_translation'] ?? '');
                $note = trim($entry['note'] ?? '');

                return $term !== '' || $meaning !== '' || $pronunciation !== '' || $pos !== '' || $example !== '' || $trans !== '' || $note !== '';
            });

            $this->merge(['entries' => array_values($filtered)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxMeanings = (int) config('polyvocab.max_meanings_per_entry', 20);
        $maxExamples = (int) config('polyvocab.max_examples_per_meaning', 10);

        // Quy tắc khi gửi nhiều từ vựng cùng lúc (Batch entries)
        if ($this->has('entries')) {
            return [
                'entries' => ['required', 'array', 'min:1'],
                'entries.*.term' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) {
                        if (trim((string) $value) === '') {
                            $fail('Từ hoặc cụm từ không được chỉ chứa khoảng trắng.');
                        }
                    },
                ],
                'entries.*.pronunciation' => ['nullable', 'string', 'max:255'],
                'entries.*.part_of_speech' => ['nullable', 'string', 'max:100'],
                'entries.*.meaning' => ['nullable', 'string', 'max:2000'],
                'entries.*.example_sentence' => [
                    'nullable',
                    'string',
                    'max:2000',
                    'required_with:entries.*.example_translation',
                    function ($attribute, $value, $fail) {
                        if ($value !== null && trim((string) $value) === '') {
                            $fail('Câu ví dụ không được chỉ chứa khoảng trắng.');
                        }
                    },
                ],
                'entries.*.example_translation' => ['nullable', 'string', 'max:2000'],
                'entries.*.note' => ['nullable', 'string', 'max:2000'],
            ];
        }

        // Quy tắc khi gửi từng mục từ (Single entry / Edit)
        return [
            'term' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (trim((string) $value) === '') {
                        $fail('Từ hoặc cụm từ không được chỉ chứa khoảng trắng.');
                    }
                },
            ],
            'pronunciation' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'meanings' => ['required', 'array', 'min:1', "max:{$maxMeanings}"],
            'meanings.*.id' => ['nullable', 'integer'],
            'meanings.*.part_of_speech' => ['nullable', 'string', 'max:100'],
            'meanings.*.meaning' => ['nullable', 'string', 'max:2000'],
            'meanings.*.examples' => ['nullable', 'array', "max:{$maxExamples}"],
            'meanings.*.examples.*.id' => ['nullable', 'integer'],
            'meanings.*.examples.*.sentence' => [
                'required_with:meanings.*.examples.*.translation',
                'nullable',
                'string',
                'max:2000',
                function ($attribute, $value, $fail) {
                    if ($value !== null && trim((string) $value) === '') {
                        $fail('Câu ví dụ không được chỉ chứa khoảng trắng.');
                    }
                },
            ],
            'meanings.*.examples.*.translation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entries.required' => 'Vui lòng nhập ít nhất một từ vựng.',
            'entries.min' => 'Vui lòng nhập ít nhất một từ vựng.',
            'entries.*.term.required' => 'Từ hoặc cụm từ không được để trống.',
            'entries.*.term.max' => 'Từ hoặc cụm từ không được vượt quá 255 ký tự.',
            'entries.*.pronunciation.max' => 'Phiên âm không được vượt quá 255 ký tự.',
            'entries.*.part_of_speech.max' => 'Từ loại không được vượt quá 100 ký tự.',
            'entries.*.meaning.max' => 'Ý nghĩa không được vượt quá 2.000 ký tự.',
            'entries.*.example_sentence.required_with' => 'Vui lòng nhập câu ví dụ khi đã có bản dịch.',
            'entries.*.example_sentence.max' => 'Câu ví dụ không được vượt quá 2.000 ký tự.',
            'entries.*.example_translation.max' => 'Bản dịch câu ví dụ không được vượt quá 2.000 ký tự.',
            'entries.*.note.max' => 'Ghi chú không được vượt quá 2.000 ký tự.',

            'term.required' => 'Từ hoặc cụm từ không được để trống.',
            'term.max' => 'Từ hoặc cụm từ không được vượt quá 255 ký tự.',
            'pronunciation.max' => 'Phiên âm không được vượt quá 255 ký tự.',
            'note.max' => 'Ghi chú không được vượt quá 2.000 ký tự.',
            'meanings.required' => 'Mục từ phải có ít nhất một ý nghĩa.',
            'meanings.min' => 'Mục từ phải có ít nhất một ý nghĩa.',
            'meanings.max' => 'Mỗi mục từ không được vượt quá :max ý nghĩa.',
            'meanings.*.meaning.max' => 'Ý nghĩa không được vượt quá 2.000 ký tự.',
            'meanings.*.part_of_speech.max' => 'Từ loại không được vượt quá 100 ký tự.',
            'meanings.*.examples.max' => 'Mỗi ý nghĩa không được vượt quá :max câu ví dụ.',
            'meanings.*.examples.*.sentence.required_with' => 'Vui lòng nhập câu ví dụ khi đã có bản dịch.',
            'meanings.*.examples.*.sentence.max' => 'Câu ví dụ không được vượt quá 2.000 ký tự.',
            'meanings.*.examples.*.translation.max' => 'Bản dịch câu ví dụ không được vượt quá 2.000 ký tự.',
        ];
    }
}
