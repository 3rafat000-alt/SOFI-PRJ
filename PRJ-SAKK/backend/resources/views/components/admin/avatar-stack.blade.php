{{--
  Component: <x-admin.avatar-stack>

  Renders an RTL-stacked row of user avatars (photo or initials fallback) with an
  optional "+N" overflow chip when the user list exceeds the visible limit.
  Uses .sakk-avatars + .sakk-avatars-more + .avatar(.avatar-sm|md|lg) from
  /sakk-admin/admin.css.  No CDN; no external resources.

  Props:
    $users    (array|Collection) — required.
                Each item should be an object or associative array with:
                  name   (string)       — full name; used for initials + aria-label.
                  avatar (string|null)  — URL to photo; null/empty → initials chip.
                  color  (string|null)  — optional override hex bg for initials chip.
              Fallback: plain strings are treated as display names (no photo).

    $max      (int, default 5)   — max avatars shown before "+N" chip appears.
    $size     (string, default 'sm') — sm | md | lg | xl — maps to .avatar-{size}.
    $tooltip  (bool, default true)  — add title="" with user name for native tooltip.
    $label    (string|null)      — accessible label for the group role="group".
                                   Default: "قائمة المستخدمين".
    $link     (bool, default false) — wrap each avatar in an <a> if item has $url.
    $overlap  (int, default 8)   — negative margin (px) between avatars (inline-start).

  Usage:
    Basic (photo + initials fallback):
      <x-admin.avatar-stack :users="$members"/>

    Max 3 visible, medium size:
      <x-admin.avatar-stack :users="$team" :max="3" size="md"/>

    Custom group label for screen readers:
      <x-admin.avatar-stack :users="$reviewers" label="المراجعون"/>

    No tooltips, large size:
      <x-admin.avatar-stack :users="$admins" size="lg" :tooltip="false"/>

    Inside a table cell:
      <td><x-admin.avatar-stack :users="$row->assignees" :max="4" size="sm"/></td>
--}}
@props([
    'users'   => [],
    'max'     => 5,
    'size'    => 'sm',
    'tooltip' => true,
    'label'   => 'قائمة المستخدمين',
    'link'    => false,
    'overlap' => 8,
])

@php
/*
 * Normalise each item so we always work with an associative array with
 * keys: name, avatar, color, url.
 */
$normalised = collect($users)->map(function ($u) {
    if (is_string($u)) {
        return ['name' => $u, 'avatar' => null, 'color' => null, 'url' => null];
    }
    $arr = is_array($u) ? $u : (method_exists($u, 'toArray') ? $u->toArray() : (array) $u);
    return [
        'name'   => $arr['name']   ?? ($arr['full_name'] ?? 'مستخدم'),
        'avatar' => $arr['avatar'] ?? ($arr['profile_photo_url'] ?? ($arr['avatar_url'] ?? null)),
        'color'  => $arr['color']  ?? null,
        'url'    => $arr['url']    ?? null,
    ];
});

$visible  = $normalised->take($max);
$overflow = $normalised->count() - $visible->count();

/*
 * Size class for each .avatar chip.
 */
$sizeClass = match($size) {
    'md' => 'avatar-md',
    'lg' => 'avatar-lg',
    'xl' => 'avatar-xl',
    default => 'avatar-sm',
};

/*
 * Pixel dimension for the overflow "+N" chip — must mirror admin.css sizes.
 */
$chipPx = match($size) {
    'md' => 36,
    'lg' => 44,
    'xl' => 56,
    default => 28,
};

/*
 * Derive initials from a display name — up to 2 Arabic/Latin characters.
 */
function avatarInitials(string $name): string {
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts) >= 2) {
        return mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr($parts[1], 0, 1, 'UTF-8');
    }
    return mb_substr($parts[0] ?? '?', 0, 2, 'UTF-8');
}

/*
 * Default palette for initials backgrounds — cycles through the SAKK palette
 * so adjacent avatars differ visually without needing per-user colour data.
 */
$palette = ['#6E1B2D', '#B58A3C', '#4A1320', '#8F6B2A', '#2A1A1F', '#6E5F63'];
@endphp

<div
    class="sakk-avatars"
    role="group"
    aria-label="{{ $label }}"
    dir="rtl"
    {{ $attributes }}
    style="--av-overlap: -{{ $overlap }}px;"
>
    {{-- Overflow chip: rendered FIRST in DOM, appears visually at inline-end due to
         .sakk-avatars flex-direction:row-reverse (RTL stacking) --}}
    @if($overflow > 0)
        <span
            class="sakk-avatars-more"
            aria-label="{{ $overflow }}+ مستخدمين إضافيين"
            @if($tooltip) title="{{ $overflow }}+ مستخدمين" @endif
            style="
                width:  {{ $chipPx }}px;
                height: {{ $chipPx }}px;
                margin-inline-start: -{{ $overlap }}px;
                font-size: {{ $size === 'xl' ? '1rem' : ($size === 'lg' ? '.875rem' : ($size === 'md' ? '.8125rem' : '.6875rem')) }};
            "
        >+{{ $overflow }}</span>
    @endif

    {{-- Avatar items — reversed because flex-direction:row-reverse handles RTL visual order --}}
    @foreach($visible->reverse() as $index => $user)
        @php
            $bg      = $user['color'] ?? $palette[$index % count($palette)];
            $initials = avatarInitials($user['name']);
            $hasPhoto = !empty($user['avatar']);
            $tipAttr  = $tooltip ? 'title="' . e($user['name']) . '"' : '';
            $wrapTag  = ($link && !empty($user['url'])) ? 'a' : 'span';
            $hrefAttr = ($wrapTag === 'a') ? 'href="' . e($user['url']) . '"' : '';
        @endphp

        <{{ $wrapTag }}
            class="avatar {{ $sizeClass }}"
            aria-label="{{ $user['name'] }}"
            {!! $tipAttr !!}
            {!! $hrefAttr !!}
            @if($wrapTag === 'a') role="link" tabindex="0" @endif
            style="
                background: {{ $hasPhoto ? 'transparent' : $bg }};
                margin-inline-start: @if(!$loop->last || $overflow > 0) -{{ $overlap }}px @else 0 @endif;
                z-index: {{ $loop->iteration }};
            "
        >
            @if($hasPhoto)
                <img
                    src="{{ $user['avatar'] }}"
                    alt="{{ $user['name'] }}"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <span
                    aria-hidden="true"
                    style="
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        height: 100%;
                        font-weight: 600;
                        color: #fff;
                        letter-spacing: 0.02em;
                        line-height: 1;
                        user-select: none;
                    "
                >{{ $initials }}</span>
            @endif
        </{{ $wrapTag }}>
    @endforeach
</div>
{{-- CSS moved to base.css (Component: Avatar Stack) --}}
