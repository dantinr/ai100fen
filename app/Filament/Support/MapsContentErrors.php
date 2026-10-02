<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait MapsContentErrors
{
    protected function persistContent(callable $save): Model
    {
        try {
            return $save();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors['data.'.(in_array($field, ['lesson', 'lessons']) ? 'status' : $field)] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }
}
