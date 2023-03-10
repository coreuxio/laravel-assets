<?php
use Assets\AssetsGateway;
use Assets\ImageGateway;
use Assets\FileGateway;
use Assets\Manipulators\Images\ImageProfileManipulator;
use Assets\Manipulators\Images\BannerImageManipulator;

return [
    AssetsGateway::class => [
        "drivers" => [
            ImageGateway::DOCUMENT_TYPE => [
                "config" => [
                    "default_manipulator" => ImageProfileManipulator::MANUPULATOR_NAME,
                    "manipulators" => [
                        ImageProfileManipulator::MANUPULATOR_NAME => [
                            'sizes' => [
                                "large" => [
                                    "x" => 700,
                                    "y" => null
                                ],
                                "medium" => [
                                    "x" => 400,
                                    "y" => null
                                ],
                                "small" => [
                                    "x" => 100,
                                    "y" => null
                                ]
                            ],
                            "class" => ImageProfileManipulator::class
                        ],
                        BannerImageManipulator::MANUPULATOR_NAME => [
                            'sizes' => [
                                "large" => [
                                    "x" => 1000,
                                    "y" => 563
                                ],
                                "medium" => [
                                    "x" => 700,
                                    "y" => 394
                                ],
                                "small" => [
                                    "x" => 400,
                                    "y" => 225
                                ]
                            ],
                            "class" => BannerImageManipulator::class
                        ],
                    ],
                    "mimes" =>
                        [
                            "image/jpeg",
                            "image/png"
                        ]
                ],
                "class" => ImageGateway::class
            ]
        ]
    ],
    FileGateway::class => [
        'cloud_base_url' => env('COULD_BASE_URL'),
        'cloud_folder' => env('CLOUD_FOLDER'),
        'local_driver' => 'local',
        'cloud_driver' => 's3',
        'local_document_folder' => storage_path('app/documents'),
        'local_document_folder_name' => 'documents',
        'keep_local_copy' => false
    ],
];