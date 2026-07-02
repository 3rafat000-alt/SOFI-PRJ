@include('errors.partials.shell', [
    'code'    => 429,
    'status'  => 'Too Many Requests',
    'tone'    => 'warning',
    'icon'    => 'timer',
    'title'   => 'طلبات كثيرة جداً',
    'message' => 'لقد تجاوزت الحدّ المسموح من الطلبات. يرجى الانتظار قليلاً ثمّ المحاولة مرة أخرى.',
    'actions' => [
        ['label' => 'إعادة المحاولة', 'onclick' => 'location.reload()', 'icon' => 'refresh'],
        ['label' => 'الرئيسية', 'href' => '/', 'primary' => true, 'icon' => 'home'],
    ],
])
