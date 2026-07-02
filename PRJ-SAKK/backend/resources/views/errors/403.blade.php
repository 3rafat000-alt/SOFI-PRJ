@include('errors.partials.shell', [
    'code'    => 403,
    'tone'    => 'danger',
    'icon'    => 'lock',
    'title'   => 'غير مصرح لك بالدخول',
    'message' => 'ليس لديك الصلاحية الكافية للوصول إلى هذه الصفحة. إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع فريق الدعم الفني.',
    'actions' => [
        ['label' => 'رجوع للخلف', 'onclick' => 'window.history.back()', 'icon' => 'back'],
        ['label' => 'العودة للوحة التحكم', 'href' => '/admin', 'primary' => true, 'icon' => 'home'],
    ],
])
