{{--
    Google Maps location picker — shared by agents create/edit.
    Reads/writes the `latitude` & `longitude` number inputs by name.
    Graceful: with no API key it shows a manual-entry hint instead of breaking.
--}}
@php($gmapsKey = config('services.google_maps.key'))

<div>
    <label class="label">حدّد الموقع على الخريطة</label>

    @if($gmapsKey)
        <div class="map-search-wrap">
            <x-heroicon name="search" />
            <input id="agentMapSearch" type="text" class="input" placeholder="ابحث عن مكان أو عنوان…" dir="auto" autocomplete="off">
        </div>
        <div id="agentMap" class="agent-map"></div>
        <p class="hint">انقر على الخريطة أو اسحب العلامة لتحديد الموقع — يُملأ خط العرض والطول تلقائياً.</p>
    @else
        <div class="flex items-start gap-2 p-3 rounded-xl text-sm" style="background: var(--surface-hover); color: var(--text-secondary);">
            <x-heroicon name="key_off" class="text-sm" style="color: var(--danger);" />
            <span>خريطة Google غير مُفعّلة — أضف <code>GOOGLE_MAPS_API_KEY</code> في ملف <code>.env</code> لتفعيل اختيار الموقع بالخريطة. حالياً أدخل الإحداثيات يدوياً من <a href="https://www.google.com/maps" target="_blank" rel="noopener" class="link">Google Maps</a> (اضغط بالزر الأيمن على الموقع).</span>
        </div>
    @endif
</div>



@if($gmapsKey)
@push('scripts')
<script>
(function () {
    var latEl = document.querySelector('input[name="latitude"]');
    var lngEl = document.querySelector('input[name="longitude"]');
    var DEFAULT = { lat: 33.5138, lng: 36.2765 }; // Syria fallback (Damascus)

    function readLatLng() {
        var lat = parseFloat(latEl && latEl.value);
        var lng = parseFloat(lngEl && lngEl.value);
        if (isFinite(lat) && isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            return { lat: lat, lng: lng, has: true };
        }
        return { lat: DEFAULT.lat, lng: DEFAULT.lng, has: false };
    }

    function writeLatLng(pos) {
        if (latEl) latEl.value = pos.lat().toFixed(7);
        if (lngEl) lngEl.value = pos.lng().toFixed(7);
    }

    window.initAgentMap = function () {
        var start = readLatLng();
        var map = new google.maps.Map(document.getElementById('agentMap'), {
            center: { lat: start.lat, lng: start.lng },
            zoom: start.has ? 16 : 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
        });

        var marker = new google.maps.Marker({
            map: map,
            position: { lat: start.lat, lng: start.lng },
            draggable: true,
            visible: start.has,
        });

        function place(latLng, recenter) {
            marker.setPosition(latLng);
            marker.setVisible(true);
            writeLatLng(latLng);
            if (recenter) map.panTo(latLng);
        }

        map.addListener('click', function (e) { place(e.latLng, false); });
        marker.addListener('dragend', function (e) { place(e.latLng, false); });

        function syncFromInputs() {
            var p = readLatLng();
            if (p.has) {
                var ll = new google.maps.LatLng(p.lat, p.lng);
                marker.setPosition(ll);
                marker.setVisible(true);
                map.panTo(ll);
            }
        }
        if (latEl) latEl.addEventListener('change', syncFromInputs);
        if (lngEl) lngEl.addEventListener('change', syncFromInputs);

        var searchEl = document.getElementById('agentMapSearch');
        if (searchEl && google.maps.places) {
            var ac = new google.maps.places.Autocomplete(searchEl, {
                fields: ['geometry'],
                componentRestrictions: { country: 'sy' },
            });
            ac.bindTo('bounds', map);
            ac.addListener('place_changed', function () {
                var pl = ac.getPlace();
                if (pl && pl.geometry && pl.geometry.location) {
                    map.setZoom(17);
                    place(pl.geometry.location, true);
                }
            });
            searchEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') e.preventDefault(); // don't submit form
            });
        }
    };

    window.gm_authFailure = function () {
        var el = document.getElementById('agentMap');
        if (el) el.innerHTML = '<div style="padding:1rem;color:var(--danger);font-size:.9rem;">'
            + 'تعذّر تحميل خريطة Google — تحقّق من صحة المفتاح وقيود النطاق (HTTP referrer) وتفعيل الفوترة.</div>';
    };
})();
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ $gmapsKey }}&libraries=places&language=ar&region=SY&callback=initAgentMap"></script>
@endpush
@endif
