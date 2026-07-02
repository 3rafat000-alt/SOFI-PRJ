@include('errors.partials.shell', [
    'code'    => 503,
    'tone'    => 'neutral',
    'icon'    => 'wrench',
    'title'   => 'الخدمة غير متاحة حالياً',
    'message' => 'نُجري حالياً بعض أعمال الصيانة لتحسين الخدمة. سنعود قريباً جداً — نشكر لك صبرك.',
    'actions' => [
        ['label' => 'إعادة المحاولة', 'onclick' => 'location.reload()', 'icon' => 'refresh'],
        ['label' => 'العودة للوحة التحكم', 'href' => '/admin', 'primary' => true, 'icon' => 'home'],
    ],
])
