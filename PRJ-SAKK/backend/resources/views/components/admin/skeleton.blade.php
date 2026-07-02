@props(['type' => 'text', 'width' => null, 'height' => null, 'count' => 1])
@for($i = 0; $i < $count; $i++)
    <div class="sakk-skeleton sakk-skeleton-{{ $type }}" @if($width) style="width:{{ $width }}" @endif @if($height) style="height:{{ $height }}" @endif></div>
@endfor
