@include('errors.partials.shell', [
    'code'    => 404,
    'tone'    => 'neutral',
    'icon'    => 'search',
    'title'   => 'الصفحة غير موجودة',
    'message' => 'لم نتمكّن من العثور على الصفحة التي تبحث عنها. قد يكون الرابط غير صحيح أو تم نقل الصفحة أو حذفها.',
    'actions' => [
        ['label' => 'رجوع للخلف', 'onclick' => 'window.history.back()', 'icon' => 'back'],
        ['label' => 'العودة للوحة التحكم', 'href' => '/admin', 'primary' => true, 'icon' => 'home'],
    ],
])
