<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class LookupRequest extends FormRequest
{
    abstract protected function permission(): string;

    public function authorize(): bool
    {
        return $this->user()?->can($this->permission()) ?? false;
    }
}
