<?php

return [

    'show_warnings' => false,

    'orientation' => 'portrait',

    'defines' => [

        'font_dir' => storage_path('fonts/'),
        'font_cache' => storage_path('fonts/'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,
        'log_output_file' => null,
        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'print',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',

        'default_font' => 'DejaVu Sans',

        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => false,
        'enable_remote' => false,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];