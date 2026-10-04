<?php

return [
    'navigation' => [
        'model' => 'Anúncio',
        'plural' => 'Anúncios',
    ],

    'form' => [
        'title' => [
            'label' => 'Título',
            'helper' => 'Título curto exibido no painel.',
        ],
        'body' => [
            'label' => 'Corpo',
            'helper' => 'Mensagem completa que os utilizadores devem ler.',
        ],
        'type' => [
            'label' => 'Tipo',
            'helper' => 'A gravidade define o ícone e as cores no painel.',
        ],
        'is_active' => [
            'label' => 'Ativo',
            'helper' => 'Anúncios inativos ficam ocultos para todos.',
        ],
        'is_dismissible' => [
            'label' => 'Dispensável',
            'helper' => 'Se estiver desligado, os utilizadores não podem fechar o aviso.',
        ],
        'starts_at' => [
            'label' => 'Início em',
            'helper' => 'Opcional. Fica oculto até este momento.',
        ],
        'expires_at' => [
            'label' => 'Expira em',
            'helper' => 'Opcional. Ocultado automaticamente após este momento.',
        ],
    ],

    'table' => [
        'created_at' => 'Criado em',
        'column_active' => 'Ativo',
        'expired' => 'Expirado',
        'filter_inactive_manual' => 'Inativo (manual)',
        'filter_expired_by_date' => 'Expirado por data',
        'filter_scheduled_future' => 'Agendado (início futuro)',
        'bulk_activate' => 'Ativar selecionados',
        'bulk_deactivate' => 'Desativar selecionados',
    ],
];
