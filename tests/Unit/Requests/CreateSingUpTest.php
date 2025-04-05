<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\API\CreateSignUpRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithTranslator;
use Tests\Traits\WithValidator;

class CreateSingUpTest extends TestCase
{
    use WithValidator;
    use WithTranslator;

    #[Test]
    #[DataProvider('scenarios')]
    public function it_validates_the_request(string $field, string|null $value, string|null $rule = null, array|null $replace = [], bool $passes = false): void
    {
        $request   = new CreateSignUpRequest([$field => $value]);
        $validator = $this->validator->make($request->all(), $request->rules());

        $this->assertEquals($validator->passes(), $passes);

        if ($rule) {
            $this->assertContains($this->translator->get("validation.$rule", ['attribute' => $field, ...$replace]), $validator->errors()->get($field));
        }
    }

    /**
     * Set of failed validation scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'email is required'                      => [
                'field' => 'email',
                'value' => null,
                'rule'  => 'required',
            ],
            'email has to be email'                  => [
                'field' => 'email',
                'value' => 'test',
                'rule'  => 'email',
            ],
            'email has to be maximum 255 characters' => [
                'field'   => 'email',
                'value'   => Str::random(256),
                'rule'    => 'max.string',
                'replace' => ['max' => 255],
            ],
            'passes validation'                      => [
                'field'  => 'email',
                'value'  => 'email@example.com',
                'passes' => true,
            ],
        ];
    }
}
