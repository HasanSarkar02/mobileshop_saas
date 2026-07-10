@props([
    'signatories' => [],
])
<div class="doc-signatures doc-signature-{{ count($signatories) }}col">
    @foreach ($signatories as $sig)
        <div class="doc-signature-block">
            <div class="doc-signature-line"></div>
            <div class="doc-signature-name">{{ $sig['name'] ?? '' }}</div>
            <div class="doc-signature-title">{{ $sig['title'] ?? '' }}</div>
        </div>
    @endforeach
</div>
