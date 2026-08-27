<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

        $this->assertSame(
            'Dashboard',
            __('Dashboard', [], 'pt_BR'),
        );
    }

    public function test_admin_and_settings_screens_are_translated_for_pt_br(): void
    {
        $this->assertSame('Usuários', __('Users', [], 'pt_BR'));
        $this->assertSame('Funções', __('Roles', [], 'pt_BR'));
        $this->assertSame('Permissões', __('Permissions', [], 'pt_BR'));
        $this->assertSame('Departamentos', __('Departments', [], 'pt_BR'));
        $this->assertSame('Logs de Auditoria', __('Audit Logs', [], 'pt_BR'));
        $this->assertSame('Administração', __('Administration', [], 'pt_BR'));
        $this->assertSame('Gestão de Usuários', __('User Management', [], 'pt_BR'));
        $this->assertSame('Segurança', __('Security', [], 'pt_BR'));
        $this->assertSame('Chaves de Acesso', __('Passkeys', [], 'pt_BR'));
        $this->assertSame('Sair', __('Log out', [], 'pt_BR'));
        $this->assertSame('Autenticação de Dois Fatores', __('Two-factor authentication', [], 'pt_BR'));
    }

    public function test_application_translation_keys_exist_in_pt_br_json(): void
    {
        $catalog = json_decode(
            (string) file_get_contents(lang_path('pt_BR.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $missing = [];

        foreach ($this->applicationTranslationKeys() as $key) {
            if (! array_key_exists($key, $catalog)) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, 'Missing pt_BR.json keys: '.implode(', ', $missing));
    }

    /**
     * @return list<string>
     */
    private function applicationTranslationKeys(): array
    {
        $roots = [
            app_path(),
            resource_path('views'),
        ];

        $keys = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                if (! str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')) {
                    continue;
                }

                $content = (string) file_get_contents($path);

                if (preg_match_all('/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/', $content, $matches) === false) {
                    continue;
                }

                foreach ($matches[2] as $key) {
                    $keys[stripcslashes($key)] = true;
                }
            }
        }

        $keys = array_keys($keys);
        sort($keys);

        return $keys;
    }
}
