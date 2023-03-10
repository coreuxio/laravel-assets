<?php

use Assets\FileGateway;
use Assets\ImageGateway;
use Assets\AssetsGateway;
use Assets\Models\GenericDocument;
use Assets\GenericDocumentGateway;
use Assets\Manipulators\Images\BannerImageManipulator;
use Assets\Manipulators\Images\ImageProfileManipulator;
use Assets\Manipulators\Generic\GenericDocumentManipulator;

return [
    AssetsGateway::class => [
        "drivers" => [
            ImageGateway::DOCUMENT_TYPE => [
                "config" => [
                    "default_manipulator" => ImageProfileManipulator::MANIPULATOR_NAME,
                    "manipulators" => [
                        ImageProfileManipulator::MANIPULATOR_NAME => [
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
                        BannerImageManipulator::MANIPULATOR_NAME => [
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
                    "mimes" => ImageGateway::MIMES
                ],
                "class" => ImageGateway::class
            ],
            GenericDocumentGateway::DOCUMENT_TYPE => [
                "config" => [
                    "default_manipulator" => GenericDocumentManipulator::MANIPULATOR_NAME,
                    "manipulators" => [
                        GenericDocumentManipulator::MANIPULATOR_NAME => [
                            'thumbnails' => [
                                GenericDocument::GENERIC => ["id" => null, "url" => "https://s3.amazonaws.com/attendee/documents/Document_Grey.png"],
                                GenericDocument::WORD => ["id" => null, "url" => "https://s3.amazonaws.com/attendee/documents/Document_Blue.png"],
                                GenericDocument::EXCEL => ["id" => null, "url" => "https://s3.amazonaws.com/attendee/documents/Icon_documents_large.png"]
                            ],
                            "mimes" => [
                                GenericDocument::WORD => GenericDocument::WORD_MIMES,
                                GenericDocument::EXCEL => GenericDocument::EXCEL_MIMES
                            ],
                            "class" => GenericDocumentManipulator::class
                        ],
                    ],
                    "mimes" => array_merge(GenericDocument::EXCEL_MIMES, GenericDocument::WORD_MIMES)
                ],
                "class" => GenericDocumentGateway::class
            ]
        ],
        "default_driver" => GenericDocumentGateway::DOCUMENT_TYPE
    ],
    FileGateway::class => [
        'cloud_base_url' => env('COULD_BASE_URL'),
        'cloud_folder' => env('CLOUD_FOLDER'),
        'local_driver' => 'local',
        'cloud_driver' => 's3',
        'local_document_folder' => storage_path('app/documents'),
        'local_document_folder_name' => 'documents',
        'keep_local_copy' => false
    ]
];

