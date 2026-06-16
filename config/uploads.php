<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locais de busca dos anexos
    |--------------------------------------------------------------------------
    |
    | O download procura o arquivo nestas raízes, NA ORDEM. A primeira que
    | contiver o arquivo é usada (com fallback para o nome solto na raiz).
    | A gravação de novos anexos usa a primeira raiz que existir como diretório.
    |
    | Local principal: storage/app/uploads do próprio novo-psweb. As pastas do
    | legado ficam apenas como fallback (transição dos arquivos antigos).
    | Para forçar outro caminho em produção, defina LEGACY_UPLOADS_PATH no .env.
    |
    */

    'paths' => array_values(array_filter([
        env('LEGACY_UPLOADS_PATH'),                 // override explícito (.env), se definido
        storage_path('app/uploads'),                // PRINCIPAL — storage do novo-psweb
        public_path('uploads'),                     // alternativa dentro do public
        base_path('../ci4/writable/uploads'),       // fallback: legado no servidor (cPanel: ci4/)
        base_path('../psweb/writable/uploads'),     // fallback: legado em desenvolvimento (psweb/)
        base_path('../../ci4/writable/uploads'),    // fallback: legado um nível acima
    ], fn ($p) => is_string($p) && $p !== '')),

];
