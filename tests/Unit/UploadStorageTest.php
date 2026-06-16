<?php

namespace Tests\Unit;

use App\Support\UploadStorage;
use Tests\TestCase;

class UploadStorageTest extends TestCase
{
    public function test_find_localiza_caminho_exato_e_fallback_na_raiz(): void
    {
        $base = storage_path('app/testes_uploads_' . uniqid());
        @mkdir($base . '/2026/06', 0775, true);
        file_put_contents($base . '/2026/06/arq.txt', 'x');
        file_put_contents($base . '/solto.pdf', 'y');

        config(['uploads.paths' => [$base]]);

        try {
            // Caminho exato (AAAA/MM/arquivo).
            $this->assertNotNull(UploadStorage::find('2026/06/arq.txt'));
            // Fallback: arquivo solto na raiz mesmo com pasta no stored_name.
            $this->assertNotNull(UploadStorage::find('2099/01/solto.pdf'));
            // Não existe em lugar nenhum.
            $this->assertNull(UploadStorage::find('2026/06/naoexiste.txt'));
            $this->assertNull(UploadStorage::find(null));
            // Raiz de gravação = primeira que existe como diretório.
            $this->assertSame(rtrim($base, '/\\'), UploadStorage::primaryRoot());
        } finally {
            @unlink($base . '/2026/06/arq.txt');
            @unlink($base . '/solto.pdf');
            @rmdir($base . '/2026/06');
            @rmdir($base . '/2026');
            @rmdir($base);
        }
    }

    public function test_primary_root_ignora_caminhos_inexistentes(): void
    {
        config(['uploads.paths' => ['/caminho/que/nao/existe/xyz', storage_path('app')]]);

        $this->assertSame(storage_path('app'), UploadStorage::primaryRoot());
    }
}
