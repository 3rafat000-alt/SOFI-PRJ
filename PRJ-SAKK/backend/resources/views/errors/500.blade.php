@include('errors.partials.shell', [
    'code'    => 500,
    'tone'    => 'danger',
    'icon'    => 'alert',
    'title'   => 'خطأ داخلي في الخادم',
    'message' => 'عذراً، حدث خطأ غير متوقع. فريق الدعم الفني على علم بالمشكلة ويعمل على حلها. يرجى المحاولة بعد قليل.',
    'actions' => [
        ['label' => 'إعادة المحاولة', 'onclick' => 'location.reload()', 'icon' => 'refresh'],
        ['label' => 'العودة للوحة التحكم', 'href' => '/admin', 'primary' => true, 'icon' => 'home'],
    ],
])
