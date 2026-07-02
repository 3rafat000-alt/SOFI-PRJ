@include('errors.partials.shell', [
    'code'    => '!',
    'status'  => 'Setup Required',
    'tone'    => 'gold',
    'icon'    => 'rocket',
    'title'   => 'التطبيق غير مثبّت',
    'message' => 'لم يتمّ تثبيت التطبيق بعد. يرجى إكمال عملية التنصيب أولاً لبدء استخدام المنصّة.',
    'note'    => 'تحتاج مساعدة في التنصيب؟ راجع دليل الإعداد أو تواصل مع الدعم.',
    'actions' => [
        ['label' => 'الذهاب إلى معالج التنصيب', 'href' => '/install', 'primary' => true, 'icon' => 'install'],
    ],
])
