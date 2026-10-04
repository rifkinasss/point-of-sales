<?php

return [
    'navigation' => [
        'model' => 'Pengumuman',
        'plural' => 'Pengumuman',
    ],

    'form' => [
        'title' => [
            'label' => 'Judul',
            'helper' => 'Judul singkat yang tampil di dashboard.',
        ],
        'body' => [
            'label' => 'Isi pengumuman',
            'helper' => 'Informasi lengkap yang perlu dibaca pengguna.',
        ],
        'type' => [
            'label' => 'Tipe',
            'helper' => 'Tipe menentukan ikon dan warna di dashboard.',
        ],
        'is_active' => [
            'label' => 'Aktif',
            'helper' => 'Pengumuman nonaktif tidak ditampilkan.',
        ],
        'is_dismissible' => [
            'label' => 'Dapat ditutup',
            'helper' => 'Jika dimatikan, pengguna tidak dapat menutup pengumuman.',
        ],
        'starts_at' => [
            'label' => 'Mulai tampil',
            'helper' => 'Opsional. Pengumuman disembunyikan hingga waktu ini.',
        ],
        'expires_at' => [
            'label' => 'Berakhir pada',
            'helper' => 'Opsional. Pengumuman otomatis disembunyikan setelah waktu ini.',
        ],
    ],

    'table' => [
        'created_at' => 'Dibuat pada',
        'column_active' => 'Aktif',
        'expired' => 'Berakhir',
        'filter_inactive_manual' => 'Nonaktif (manual)',
        'filter_expired_by_date' => 'Berakhir berdasarkan tanggal',
        'filter_scheduled_future' => 'Terjadwal (mulai nanti)',
        'bulk_activate' => 'Aktifkan pilihan',
        'bulk_deactivate' => 'Nonaktifkan pilihan',
    ],
];
