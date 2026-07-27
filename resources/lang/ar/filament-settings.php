<?php

return [
    'navigation' => [
        'label' => 'الإعدادات',
        'title' => 'الإعدادات',
    ],

    'tabs' => [
        'general' => [
            'label' => 'عام',
            'fields' => [
                'app_name'        => 'اسم التطبيق',
                'app_description' => 'وصف التطبيق',
                'logo'            => 'الشعار',
                'favicon'         => 'أيقونة الموقع',
                'support_email'   => 'البريد الإلكتروني للدعم',
                'support_phone'   => 'هاتف الدعم',
            ],
        ],

        'social_links' => [
            'label'    => 'روابط التواصل الاجتماعي',
            'fields'   => [
                'links' => 'الروابط',
                'name'  => 'المنصة',
                'url'   => 'الرابط',
            ],
            'add_link' => 'إضافة رابط',
        ],

        'static_pages' => [
            'label'          => 'الصفحات الثابتة',
            'privacy_policy' => 'سياسة الخصوصية',
            'terms'          => 'الشروط والأحكام',
            'about'          => 'عن التطبيق',
            'content'        => 'المحتوى',
        ],
    ],

    'actions' => [
        'save' => 'حفظ الإعدادات',
    ],

    'notifications' => [
        'saved'        => 'تم حفظ الإعدادات بنجاح.',
        'unauthorized' => 'ليس لديك صلاحية تنفيذ هذا الإجراء.',
    ],
];
