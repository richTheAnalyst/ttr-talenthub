{{-- <x-whatsapp-share :text="'Check out this job'" :url="route('jobs.show', $job)" /> --}}
@props(['text', 'url'])
<a href="https://wa.me/?text={{ rawurlencode($text."\n".$url) }}" target="_blank" rel="noopener"
   {{ $attributes->merge(['class' => 'btn-ghost btn-sm no-underline']) }}>
    <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.800s.7-2 .9-2.300c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.500l.8 2c.1.2.1.4 0 .5l-.4.6-.3.400c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.100c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.8-.1 1.400Z"/></svg>
    {{ $slot->isEmpty() ? 'Share' : $slot }}
</a>