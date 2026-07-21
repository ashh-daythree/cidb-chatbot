<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class MalaysianStateValidator extends AbstractValidator
{
    /**
     * @var array<string, array{code: string, name: string, aliases: array<int, string>}>
     */
    private array $states = [
        'johor' => ['code' => 'JHR', 'name' => 'Johor', 'aliases' => ['johor', 'jhr']],
        'kedah' => ['code' => 'KDH', 'name' => 'Kedah', 'aliases' => ['kedah', 'kdh']],
        'kelantan' => ['code' => 'KTN', 'name' => 'Kelantan', 'aliases' => ['kelantan', 'ktn']],
        'melaka' => ['code' => 'MLK', 'name' => 'Melaka', 'aliases' => ['melaka', 'mlk']],
        'negeri sembilan' => ['code' => 'NSN', 'name' => 'Negeri Sembilan', 'aliases' => ['negeri sembilan', 'nsn']],
        'pahang' => ['code' => 'PHG', 'name' => 'Pahang', 'aliases' => ['pahang', 'phg']],
        'perak' => ['code' => 'PRK', 'name' => 'Perak', 'aliases' => ['perak', 'prk']],
        'perlis' => ['code' => 'PLS', 'name' => 'Perlis', 'aliases' => ['perlis', 'pls']],
        'pulau pinang' => ['code' => 'PNG', 'name' => 'Pulau Pinang', 'aliases' => ['pulau pinang', 'penang', 'png']],
        'sabah' => ['code' => 'SBH', 'name' => 'Sabah', 'aliases' => ['sabah', 'sbh']],
        'sarawak' => ['code' => 'SWK', 'name' => 'Sarawak', 'aliases' => ['sarawak', 'swk']],
        'selangor' => ['code' => 'SGR', 'name' => 'Selangor', 'aliases' => ['selangor', 'sgr']],
        'terengganu' => ['code' => 'TRG', 'name' => 'Terengganu', 'aliases' => ['terengganu', 'trg']],
        'w.p. kuala lumpur' => ['code' => 'WPKL', 'name' => 'W.P. Kuala Lumpur', 'aliases' => ['w.p. kuala lumpur', 'wp kuala lumpur', 'wilayah persekutuan kuala lumpur', 'wpk l', 'wpkl']],
        'w.p. labuan' => ['code' => 'WPLB', 'name' => 'W.P. Labuan', 'aliases' => ['w.p. labuan', 'wp labuan', 'wilayah persekutuan labuan', 'wplb']],
        'w.p. putrajaya' => ['code' => 'WPPJ', 'name' => 'W.P. Putrajaya', 'aliases' => ['w.p. putrajaya', 'wp putrajaya', 'wilayah persekutuan putrajaya', 'wppj']],
    ];

    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['state'] ?? $input['state_name'] ?? $input['state_code'] ?? '') : $input);

        if ($value === '') {
            return $this->invalid('state', 'state_required', 'Malaysian state is required.');
        }

        $needle = mb_strtolower($this->collapseWhitespace($value));

        foreach ($this->states as $state) {
            if (in_array($needle, $state['aliases'], true)) {
                return ValidationResult::success([
                    'state_code' => $state['code'],
                    'state_name' => $state['name'],
                ]);
            }
        }

        return $this->invalid(
            'state',
            'state_invalid',
            'Invalid Malaysian state selected.',
            $value,
            ['allowed' => array_column($this->states, 'name')]
        );
    }

    /**
     * @return array<string, array{code: string, name: string, aliases: array<int, string>}>
     */
    public function allowedStates(): array
    {
        return $this->states;
    }
}

