<?php

declare(strict_types=1);

namespace Hexlet\Code;

use Valitron\Validator as ValitronValidator;

class Validator
{
    public function validate(array $urlData): array
    {
        $validator = new ValitronValidator($urlData);

        $validator->rule('required', 'url')
            ->message('URL не должен быть пустым');

        $validator->rule('url', 'url')
            ->message('Некорректный URL');

        $validator->rule('lengthMax', 'url', 255)
            ->message('URL превышает 255 символов');

        if ($validator->validate()) {
            return [];
        }

        return $validator->errors();
    }
}
