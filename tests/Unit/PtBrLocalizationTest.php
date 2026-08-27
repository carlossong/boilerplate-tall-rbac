<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class PtBrLocalizationTest extends TestCase
{
    public function test_framework_strings_are_translated_for_pt_br(): void
    {
        $this->assertSame(
            'O campo :attribute deve ser aceito.',
            trans('validation.accepted', [], 'pt_BR'),
        );

        $this->assertSame(
            'Cancelar',
            __('Cancel', [], 'pt_BR'),
        );
    }
}
