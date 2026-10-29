{{-- "Turn on phone notifications" card. Renders nothing until VAPID keys are configured. --}}
@if (filled(config('talenthub.push.public_key')))
<section id="push-card" class="card mb-5 hidden" aria-labelledby="push-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 id="push-title" class="text-base font-semibold">Phone notifications</h2>
            <p id="push-hint" class="text-sm text-ink-500">Get job alerts and application updates even when the app is closed.</p>
        </div>
        <button id="push-btn" type="button" class="btn-primary btn-sm">Turn on</button>
    </div>
</section>

<script>
(function () {
    var card = document.getElementById('push-card');
    var btn = document.getElementById('push-btn');
    var hint = document.getElementById('push-hint');
    var csrf = @json(csrf_token());
    var publicKey = @json(config('talenthub.push.public_key'));
    if (!card) return;

    var supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    card.classList.remove('hidden');

    if (!supported) {
        btn.classList.add('hidden');
        hint.textContent = 'On iPhone, first tap Share and "Add to Home Screen", then open the app from your home screen to turn this on.';
        return;
    }

    function b64ToBytes(s) {
        var pad = '='.repeat((4 - s.length % 4) % 4);
        var raw = atob((s + pad).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, function (c) { return c.charCodeAt(0); });
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        });
    }

    function render(on) {
        btn.textContent = on ? 'Turn off' : 'Turn on';
        btn.className = on ? 'btn-ghost btn-sm' : 'btn-primary btn-sm';
        hint.textContent = on ? 'On for this device. You will get alerts even when the app is closed.' : 'Get job alerts and application updates even when the app is closed.';
    }

    navigator.serviceWorker.ready.then(function (reg) {
        reg.pushManager.getSubscription().then(function (sub) { render(!!sub && Notification.permission === 'granted'); });

        btn.addEventListener('click', function () {
            btn.disabled = true;
            reg.pushManager.getSubscription().then(function (sub) {
                if (sub) {
                    return post('{{ route('push.unsubscribe') }}', { endpoint: sub.endpoint })
                        .then(function () { return sub.unsubscribe(); })
                        .then(function () { render(false); });
                }
                return Notification.requestPermission().then(function (perm) {
                    if (perm !== 'granted') { hint.textContent = 'Notifications are blocked. Allow them in your browser or phone settings, then try again.'; return; }
                    return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(publicKey) })
                        .then(function (s) { return post('{{ route('push.subscribe') }}', s.toJSON()); })
                        .then(function () { render(true); });
                });
            }).catch(function () { hint.textContent = 'Could not change this setting. Please try again.'; })
              .finally(function () { btn.disabled = false; });
        });
    });
})();
</script>
@endif