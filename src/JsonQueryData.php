<?php

namespace KadirGun\JsonQuery;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class JsonQueryData extends FormRequest
{
    public function methods(): array
    {
        return $this->input('methods', []);
    }

    public function rules(): array
    {
        return [
            'methods' => 'required|array',
            'methods.*.name' => 'required|string',
            'methods.*.parameters' => 'nullable|array',
        ];
    }

    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new self);
    }
}
