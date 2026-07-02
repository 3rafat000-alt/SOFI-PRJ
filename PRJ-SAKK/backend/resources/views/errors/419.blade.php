@include('errors.partials.shell', [
    'code'    => 419,
    'tone'    => 'warning',
    'icon'    => 'clock',
    'title'   => 'انتهت صلاحية الجلسة',
    'message' => 'انتهت صلاحية جلسة العمل الخاصة بك. لأسباب أمنية، يرجى تحديث الصفحة وتسجيل الدخول مرة أخرى.',
    'actions' => [
        ['label' => 'تحديث الصفحة', 'onclick' => 'location.reload()', 'icon' => 'refresh'],
        ['label' => 'تسجيل الدخول', 'href' => '/admin/login', 'primary' => true, 'icon' => 'login'],
    ],
])
