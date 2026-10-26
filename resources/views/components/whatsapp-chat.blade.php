{{-- Opens a WhatsApp chat with the number in WHATSAPP_NUMBER. Renders nothing when it is not set. --}}
@props(['message' => 'Hello TTR, I need some help.'])
@php $number = preg_replace('/\D+/', '', (string) config('talenthub.whatsapp_number')); @endphp
@if ($number)
    <a href="https://wa.me/{{ $number }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener"
       {{ $attributes->merge(['class' => 'btn-ghost no-underline']) }}>
        <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 .9-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.3.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.8-.1 1.4Z"/></svg>
        {{ $slot->isEmpty() ? 'Chat on WhatsApp' : $slot }}
    </a>
@endif