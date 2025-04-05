<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\API\CreateContactRequest;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithTranslator;
use Tests\Traits\WithValidator;

class CreateContactTest extends TestCase
{
    use WithValidator;
    use WithTranslator;

    #[Test]
    #[DataProvider('scenarios')]
    public function it_validates_the_request(string $field, mixed $value, string|null $rule, array|null $replace = []): void
    {
        $request   = new CreateContactRequest([$field => $value]);
        $validator = $this->validator->make($request->all(), $request->rules());

        $this->assertFalse($validator->passes());

        if ($rule) {
            $this->assertContains($this->translator->get("validation.$rule", ['attribute' => $field, ...$replace]), $validator->errors()->get($field));
        }
    }

    #[Test]
    public function it_passes_validation(): void
    {
        $request = new CreateContactRequest([
            'name'    => 'test',
            'email'   => 'email@example.com',
            'message' => 'test',
        ]);

        $validator = $this->validator->make($request->all(), $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Set of failed validation scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'name is required'                         => [
                'field' => 'name',
                'value' => null,
                'rule'  => 'required',
            ],
            'name has to be string'                    => [
                'field' => 'name',
                'value' => 1,
                'rule'  => 'string',
            ],
            'name has to be minimum 3 characters'      => [
                'field'   => 'name',
                'value'   => Str::random(2),
                'rule'    => 'min.string',
                'replace' => ['min' => 3],
            ],
            'name has to be maximum 50 characters'     => [
                'field'   => 'name',
                'value'   => Str::random(51),
                'rule'    => 'max.string',
                'replace' => ['max' => 50],
            ],
            'email is required'                        => [
                'field' => 'email',
                'value' => null,
                'rule'  => 'required',
            ],
            'email has to be email'                    => [
                'field' => 'email',
                'value' => 'test',
                'rule'  => 'email',
            ],
            'message is required'                      => [
                'field' => 'message',
                'value' => null,
                'rule'  => 'required',
            ],
            'message has to be string'                 => [
                'field' => 'message',
                'value' => 1,
                'rule'  => 'string',
            ],
            'message has to be minimum 3 characters'   => [
                'field'   => 'message',
                'value'   => Str::random(2),
                'rule'    => 'min.string',
                'replace' => ['min' => 3],
            ],
            'message has to be maximum 200 characters' => [
                'field'   => 'message',
                'value'   => Str::random(201),
                'rule'    => 'max.string',
                'replace' => ['max' => 200],
            ],
        ];
    }
}
