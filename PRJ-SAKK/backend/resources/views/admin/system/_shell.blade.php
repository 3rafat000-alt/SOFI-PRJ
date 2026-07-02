{{--
    Shared premium styles for the "إعدادات النظام" suite (light-minimal · single burgundy).
    Coordinated visual language across: third-party · channels · messages · maintenance.
    Builds on the global design tokens in layouts/admin.blade.php (:root).
--}}
{{-- Styles moved to base.css --}}

@once
@push('scripts')
<script>
    // Copy helper used across the system suite (variables, webhook URLs).
    window.sysCopy = function (text, el) {
        navigator.clipboard.writeText(text).then(function () {
            if (!el) return;
            var old = el.getAttribute('data-label') || el.textContent;
            el.classList.add('badge-success');
            var icon = el.querySelector('svg[data-slot="icon"]');
            if (icon) { var oi = icon.textContent; icon.textContent = 'check'; setTimeout(function(){ icon.textContent = oi; el.classList.remove('badge-success'); }, 1200); }
        });
    };
</script>
@endpush
@endonce
